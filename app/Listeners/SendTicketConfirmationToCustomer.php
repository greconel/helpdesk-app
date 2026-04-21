<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\TicketMessage;
use App\Services\GraphMailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendTicketConfirmationToCustomer implements ShouldQueue
{
    public int $tries   = 3;       
    public int $backoff = 30;      

    public function __construct(private GraphMailService $graph) {}

    public function handle(TicketCreated $event): void
    {
        if (!$event->sendConfirmation) {
            return;
        }

        $ticket   = $event->ticket;
        $customer = $ticket->customer;

        if (!$customer?->email) {
            return;
        }

        $html = view('emails.ticket-confirmation', ['ticket' => $ticket])->render();

        $payload = [
            'message' => [
                'subject' => "[{$ticket->ticket_number}] Bevestiging: {$ticket->subject}",
                'body'    => [
                    'contentType' => 'HTML',
                    'content'     => $html,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $customer->email,
                            'name'    => $customer->name,
                        ],
                    ],
                ],
                'internetMessageHeaders' => [
                    [
                        'name'  => 'X-Ticket-ID',
                        'value' => (string) $ticket->id,
                    ],
                    [
                        'name'  => 'X-Ticket-Number',
                        'value' => $ticket->ticket_number,
                    ],
                ],
            ],
            'saveToSentItems' => true,
        ];

        try {
            $sent = $this->graph->sendMail($payload);

            if ($sent) {
                TicketMessage::create([
                    'ticket_id'           => $ticket->id,
                    'user_id'             => null,
                    'from_email'          => config('services.microsoft.mailbox'),
                    'from_name'           => 'Helpdesk',
                    'direction'           => 'outbound',
                    'subject'             => "[{$ticket->ticket_number}] Bevestiging: {$ticket->subject}",
                    'body_html'           => $html,
                    'body_text'           => strip_tags($html),
                    'message_id'          => null,
                    'in_reply_to'         => null,
                    'internet_message_id' => null,
                    'sent_at'             => now(),
                ]);

                Log::info("Bevestigingsmail verstuurd voor ticket {$ticket->ticket_number} naar {$customer->email}");
            }
        } catch (\Throwable $e) {
            Log::error("Bevestigingsmail mislukt voor ticket {$ticket->ticket_number}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}