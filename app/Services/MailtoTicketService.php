<?php
namespace App\Services;

use App\Events\TicketCreated;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailToTicketService
{
    public function __construct(private GraphMailService $graph) {}

    public function processUnreadMails(): void
    {
        $messages = $this->graph->getUnreadMessages(25);

        foreach ($messages as $msg) {
            try {
                $this->processMessage($msg);
                $this->graph->markAsRead($msg['id']);
            } catch (\Throwable $e) {
                Log::error('Mail verwerken mislukt', [
                    'message_id' => $msg['id'],
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    private function sanitizeHtml(?string $html): ?string
    {
        if (!$html) return $html;

        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
        $html = preg_replace('/<\/?(html|body)[^>]*>/i', '', $html);

        return trim($html);
    }

    public function processMessage(array $msg): void
    {
        $graphId         = $msg['id'];
        $internetMsgId   = $msg['internetMessageId'] ?? null;
        $subject         = $msg['subject'] ?? '(geen onderwerp)';
        $fromEmail       = data_get($msg, 'from.emailAddress.address');
        $fromName        = data_get($msg, 'from.emailAddress.name', $fromEmail);
        $bodyHtml        = $this->sanitizeHtml(data_get($msg, 'body.content'));
        $hasAttachments  = $msg['hasAttachments'] ?? false;
        $hasInlineImages = str_contains($bodyHtml ?? '', 'cid:');

        if ($hasAttachments || $hasInlineImages) {
            $attachments = $this->graph->getAttachments($graphId);
            foreach ($attachments as $attachment) {
                if (!empty($attachment['isInline'])
                    && !empty($attachment['contentId'])
                    && !empty($attachment['contentBytes'])
                    && str_starts_with($attachment['contentType'] ?? '', 'image/')) {

                    $contentId    = $attachment['contentId'];
                    $contentBytes = base64_decode($attachment['contentBytes']);
                    $filename     = uniqid('inline_', true) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $attachment['name'] ?? 'image.png');
                    $path         = 'attachments/' . $filename;

                    Storage::disk('public')->put($path, $contentBytes);
                    $url      = '/storage/' . $path;
                    $bodyHtml = str_replace("cid:$contentId", $url, $bodyHtml);
                }
            }
        }

        $bodyText = strip_tags($bodyHtml ?? '');

        $receivedAtRaw = $msg['receivedDateTime'] ?? null;
        $appTimezone   = config('app.timezone', 'UTC');

        try {
            $receivedAt = $receivedAtRaw
                ? Carbon::parse($receivedAtRaw)->setTimezone($appTimezone)
                : now($appTimezone);
        } catch (\Throwable $e) {
            Log::warning('receivedDateTime kon niet geparsed worden, fallback naar now(app.timezone)', [
                'message_id'       => $graphId,
                'receivedDateTime' => $receivedAtRaw,
                'error'            => $e->getMessage(),
            ]);
            $receivedAt = now($appTimezone);
        }

        if (TicketMessage::where('message_id', $graphId)->exists()) {
            return;
        }

        $headers   = $this->graph->getMessageHeaders($graphId);
        $inReplyTo = $headers['in-reply-to'] ?? null;

        $ticket = $this->findExistingTicket($inReplyTo, $subject);

        $isNewTicket = false;

        if (!$ticket) {
            $isNewTicket = true;

            $customer = Customer::firstOrCreate(
                ['email' => $fromEmail],
                ['name'  => $fromName]
            );

            $ticket = Ticket::create([
                'ticket_number'           => Ticket::generateTicketNumber(),
                'subject'                 => $subject,
                'description'             => $bodyHtml,
                'status'                  => 'new',
                'customer_id'             => $customer->id,
                'source'                  => 'email',
                'last_inbound_message_id' => $internetMsgId,
            ]);
        } else {
            $ticket->update([
                'last_inbound_message_id' => $internetMsgId,
                'status' => $ticket->status === 'closed' ? 'new' : $ticket->status,
            ]);
        }

        TicketMessage::create([
            'ticket_id'           => $ticket->id,
            'from_email'          => $fromEmail,
            'from_name'           => $fromName,
            'direction'           => 'inbound',
            'subject'             => $subject,
            'body_html'           => $bodyHtml,
            'body_text'           => $bodyText,
            'message_id'          => $graphId,
            'in_reply_to'         => $inReplyTo,
            'internet_message_id' => $internetMsgId,
            'sent_at'             => $receivedAt->toDateTimeString(),
        ]);

        if ($isNewTicket) {
            event(new TicketCreated($ticket));
        } else {
            // Analyseer de reply via AI
            $this->analyseIncomingReply($ticket, $bodyText);
        }
    }

    private function analyseIncomingReply(Ticket $ticket, string $bodyText): void
    {
        // Geen analyse nodig als ticket al gesloten is
        if ($ticket->status === 'closed') {
            return;
        }

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 100,
            'messages'   => [[
                'role'    => 'user',
                'content' => $this->buildReplyAnalysisPrompt($bodyText),
            ]],
        ]);

        if (!$response->successful()) {
            Log::warning("AI reply-analyse mislukt voor ticket {$ticket->ticket_number}", [
                'body' => $response->body(),
            ]);
            return;
        }

        $content = trim($response->json('content.0.text'));
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*/i', '', $content);
        $content = trim($content);

        $result = json_decode($content, true);

        if (!$result || !isset($result['action'])) {
            return;
        }

        match ($result['action']) {
            'close'    => $ticket->update([
                            'status'    => 'closed',
                            'closed_at' => now(),
                          ]),
            'escalate' => $ticket->update(['impact' => 'high']),
            'nothing'  => null,
            default    => null,
        };

        if ($result['action'] !== 'nothing') {
            Log::info("AI reply-analyse actie uitgevoerd voor ticket {$ticket->ticket_number}", [
                'action' => $result['action'],
                'reden'  => $result['reason'] ?? '—',
            ]);
        }
    }

    private function buildReplyAnalysisPrompt(string $body): string
    {
        $tekst = substr($body, 0, 500);

        return <<<PROMPT
Analyseer de volgende e-mail reply van een klant aan een helpdesk.

Bepaal welke actie het systeem moet ondernemen op basis van de inhoud.

E-mail:
"{$tekst}"

Mogelijke acties:
- "close": de klant geeft aan dat het probleem opgelost is, ze er geen hulp meer bij nodig hebben, of ze het verzoek intrekken
- "escalate": de klant is gefrustreerd, het probleem is dringender geworden, of ze dreigen te escaleren
- "nothing": gewone reactie, vraag om info, of onduidelijk

Antwoord ALLEEN in dit JSON formaat:
{
  "action": "close",
  "reason": "Klant geeft aan dat het probleem opgelost is"
}
PROMPT;
    }

    private function findExistingTicket(?string $inReplyTo, string $subject): ?Ticket
    {
        if ($inReplyTo) {
            $msg = TicketMessage::where('internet_message_id', $inReplyTo)
                ->orWhere('internet_message_id', trim($inReplyTo, '<>'))
                ->first();

            if ($msg) return $msg->ticket;

            $ticket = Ticket::where('last_inbound_message_id', $inReplyTo)->first();
            if ($ticket) return $ticket;
        }

        if (preg_match('/\[#(\d+)\]/', $subject, $matches)) {
            $ticketNumber = '#' . str_pad($matches[1], 4, '0', STR_PAD_LEFT);
            $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
            if ($ticket) return $ticket;
        }

        return null;
    }
}