<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
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

    public function processMessage(array $msg): void
    {
        $graphId         = $msg['id'];
        $internetMsgId   = $msg['internetMessageId'] ?? null;
        $subject         = $msg['subject'] ?? '(geen onderwerp)';
        $fromEmail       = data_get($msg, 'from.emailAddress.address');
        $fromName        = data_get($msg, 'from.emailAddress.name', $fromEmail);
        $bodyHtml        = data_get($msg, 'body.content');
        $bodyText        = strip_tags($bodyHtml ?? '');
        $receivedAt      = $msg['receivedDateTime'] ?? now()->toISOString();
        $hasAttachments  = $msg['hasAttachments'] ?? false;

        // Duplicate check
        if (TicketMessage::where('message_id', $graphId)->exists()) {
            return;
        }

        // Headers ophalen voor In-Reply-To
        $headers    = $this->graph->getMessageHeaders($graphId);
        $inReplyTo  = $headers['in-reply-to'] ?? null;

        // Bestaand ticket zoeken via In-Reply-To
        $ticket = $this->findExistingTicket($inReplyTo);

        if (!$ticket) {
            // Nieuw ticket aanmaken
            $customer = Customer::firstOrCreate(
                ['email' => $fromEmail],
                ['name'  => $fromName]
            );

            $ticket = Ticket::create([
                'ticket_number'            => $this->generateTicketNumber(),
                'subject'                  => $subject,
                'description'              => $bodyText,
                'status'                   => 'new',
                'customer_id'              => $customer->id,
                'source'                   => 'email',
                'last_inbound_message_id'  => $internetMsgId,
            ]);
        } else {
            // Bestaand ticket updaten
            $ticket->update([
                'last_inbound_message_id' => $internetMsgId,
                'status' => $ticket->status === 'closed' ? 'new' : $ticket->status,
            ]);
        }

        // Bericht opslaan
        $ticketMessage = TicketMessage::create([
            'ticket_id'          => $ticket->id,
            'from_email'         => $fromEmail,
            'from_name'          => $fromName,
            'direction'          => 'inbound',
            'subject'            => $subject,
            'body_html'          => $bodyHtml,
            'body_text'          => $bodyText,
            'message_id'         => $graphId,
            'in_reply_to'        => $inReplyTo,
            'internet_message_id'=> $internetMsgId,
            'sent_at'            => $receivedAt,
        ]);

        // Bijlagen verwerken
        if ($hasAttachments) {
            $this->processAttachments($graphId, $ticket->id, $ticketMessage->id);
        }
    }

    private function findExistingTicket(string $inReplyTo = null): ?Ticket
    {
        if (!$inReplyTo) return null;

        // Zoek op internet_message_id van eerder gestuurde outbound berichten
        $msg = TicketMessage::where('internet_message_id', $inReplyTo)
            ->orWhere('internet_message_id', trim($inReplyTo, '<>'))
            ->first();

        if ($msg) return $msg->ticket;

        // Fallback: zoek op last_inbound_message_id
        return Ticket::where('last_inbound_message_id', $inReplyTo)->first();
    }

    private function processAttachments(string $graphId, int $ticketId, int $messageId): void
    {
        $attachments = $this->graph->getAttachments($graphId);

        foreach ($attachments as $att) {
            if (($att['@odata.type'] ?? '') !== '#microsoft.graph.fileAttachment') continue;

            $filename  = $att['name'] ?? 'bijlage';
            $mimeType  = $att['contentType'] ?? 'application/octet-stream';
            $content   = base64_decode($att['contentBytes'] ?? '');
            $path      = "tickets/{$ticketId}/attachments/" . uniqid() . '_' . $filename;

            Storage::disk('local')->put($path, $content);

            TicketAttachment::create([
                'ticket_id'         => $ticketId,
                'ticket_message_id' => $messageId,
                'filename'          => $filename,
                'mime_type'         => $mimeType,
                'size'              => strlen($content),
                'disk'              => 'local',
                'path'              => $path,
            ]);
        }
    }

    private function generateTicketNumber(): string
    {
        $last = Ticket::orderBy('id', 'desc')->first();
        $n    = $last ? ((int) str_replace('#', '', $last->ticket_number)) + 1 : 1;
        return '#' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}