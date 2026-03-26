<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\TicketMessage;
use App\Services\GraphMailService;
use Illuminate\Support\Facades\Log;

class SendTicketConfirmationToCustomer
{
    public function __construct(private GraphMailService $graph) {}

    public function handle(TicketCreated $event): void
    {
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
                // Haal het verstuurde bericht op uit de Sent Items
                // om de door Graph gegenereerde Message-ID te achterhalen
                $internetMessageId = $this->getSentMessageId($ticket->ticket_number);

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
                    'internet_message_id' => $internetMessageId,
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

    private function getSentMessageId(string $ticketNumber): ?string
    {
        // Kort wachten zodat Graph de mail heeft opgeslagen in Sent Items
        sleep(2);

        $mailbox = config('services.microsoft.mailbox');
        $token   = $this->graph->getAccessToken();

        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/sentItems/messages", [
                '$top'     => 1,
                '$select'  => 'internetMessageId,subject',
                '$orderby' => 'sentDateTime desc',
                '$filter'  => "contains(subject, '{$ticketNumber}')",
            ]);

        if ($response->successful()) {
            $messages = $response->json('value', []);
            if (!empty($messages[0]['internetMessageId'])) {
                return $messages[0]['internetMessageId'];
            }
        }

        return null;
    }
}