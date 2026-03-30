<?php
namespace App\Services;

use App\Events\TicketCreated;
use App\Models\Customer;
use App\Models\Ticket;
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

        $bodyText   = strip_tags($bodyHtml ?? '');
        $receivedAt = $msg['receivedDateTime'] ?? now()->toISOString();

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
                'ticket_number'           => $this->generateTicketNumber(),
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
            'sent_at' => \Carbon\Carbon::parse($receivedAt)->setTimezone('UTC'),
        ]);

        if ($isNewTicket) {
            event(new TicketCreated($ticket));
        }
    }

    private function findExistingTicket(?string $inReplyTo, string $subject): ?Ticket
    {
        // 1. Zoek op in-reply-to header
        if ($inReplyTo) {
            $msg = TicketMessage::where('internet_message_id', $inReplyTo)
                ->orWhere('internet_message_id', trim($inReplyTo, '<>'))
                ->first();

            if ($msg) return $msg->ticket;

            $ticket = Ticket::where('last_inbound_message_id', $inReplyTo)->first();
            if ($ticket) return $ticket;
        }

        // 2. Odoo-stijl fallback: zoek op ticket nummer in het onderwerp (bijv. "[#0045]")
        if (preg_match('/\[#(\d+)\]/', $subject, $matches)) {
            $ticketNumber = '#' . str_pad($matches[1], 4, '0', STR_PAD_LEFT);
            $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
            if ($ticket) return $ticket;
        }

        return null;
    }

    private function generateTicketNumber(): string
    {
        $last = Ticket::orderBy('id', 'desc')->first();
        $n    = $last ? ((int) str_replace('#', '', $last->ticket_number)) + 1 : 1;
        return '#' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}