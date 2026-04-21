<?php

namespace App\Services;

use App\Events\TicketCreated;
use App\Events\TicketReplyReceived;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MailToTicketService
{
    public function __construct(
        private GraphMailService   $graph,
        private AttachmentProcessor $attachmentProcessor,
    ) {}

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
        $graphId        = $msg['id'];
        $internetMsgId  = $msg['internetMessageId'] ?? null;
        $subject        = $msg['subject'] ?? '(geen onderwerp)';
        $fromEmail      = data_get($msg, 'from.emailAddress.address');
        $fromName       = data_get($msg, 'from.emailAddress.name', $fromEmail);
        $hasAttachments = $msg['hasAttachments'] ?? false;

        // HTML body voorbereiden — inline afbeeldingen vervangen indien aanwezig
        $bodyHtml = $this->sanitizeHtml(data_get($msg, 'body.content'));

        if ($hasAttachments || str_contains($bodyHtml ?? '', 'cid:')) {
            $bodyHtml = $this->attachmentProcessor->processInlineImages($bodyHtml ?? '', $graphId);
        }

        $bodyText = strip_tags($bodyHtml ?? '');

        // Duplicaat check
        if (TicketMessage::where('message_id', $graphId)->exists()) {
            return;
        }

        $receivedAt = $this->parseReceivedAt($msg['receivedDateTime'] ?? null, $graphId);

        // Threading: zoek bestaand ticket op basis van headers of ticketnummer
        $headers      = $this->graph->getMessageHeaders($graphId);
        $inReplyTo    = $headers['in-reply-to'] ?? null;
        $ticket       = $this->findExistingTicket($inReplyTo, $subject);
        $isNewTicket  = $ticket === null;

        // Ticket aanmaken of updaten
        if ($isNewTicket) {
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

        // Bericht opslaan
        $message = TicketMessage::create([
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

        // Bijlagen verwerken
        if ($hasAttachments) {
            $this->attachmentProcessor->processAttachments($graphId, $ticket, $message);
        }

        // Events afvuren — listeners doen de rest
        if ($isNewTicket) {
            event(new TicketCreated($ticket));
        } else {
            TicketReplyReceived::dispatch($ticket, $bodyText);
        }
    }

    // ─── private helpers ───────────────────────────────────────────────────

    private function sanitizeHtml(?string $html): ?string
    {
        if (! $html) {
            return $html;
        }

        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);
        $html = preg_replace('/<\/?(html|body)[^>]*>/i', '', $html);

        return trim($html);
    }

    private function parseReceivedAt(?string $raw, string $graphId): Carbon
    {
        $timezone = config('app.timezone', 'UTC');

        try {
            return $raw
                ? Carbon::parse($raw)->setTimezone($timezone)
                : now($timezone);
        } catch (\Throwable $e) {
            Log::warning('receivedDateTime kon niet geparsed worden, fallback naar now()', [
                'message_id'       => $graphId,
                'receivedDateTime' => $raw,
                'error'            => $e->getMessage(),
            ]);

            return now($timezone);
        }
    }

    private function findExistingTicket(?string $inReplyTo, string $subject): ?Ticket
    {
        if ($inReplyTo) {
            $msg = TicketMessage::where('internet_message_id', $inReplyTo)
                ->orWhere('internet_message_id', trim($inReplyTo, '<>'))
                ->first();

            if ($msg) {
                return $msg->ticket;
            }

            $ticket = Ticket::where('last_inbound_message_id', $inReplyTo)->first();
            if ($ticket) {
                return $ticket;
            }
        }

        if (preg_match('/\[#(\d+)\]/', $subject, $matches)) {
            $ticketNumber = '#' . str_pad($matches[1], 4, '0', STR_PAD_LEFT);
            return Ticket::where('ticket_number', $ticketNumber)->first();
        }

        return null;
    }
}