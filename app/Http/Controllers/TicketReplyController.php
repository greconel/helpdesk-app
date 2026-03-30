<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\GraphMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketReplyController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $request->validate([
            'body' => 'required|string|min:1',
        ]);

        $user    = auth()->user();
        $graph   = app(GraphMailService::class);
        $bodyText = strip_tags($request->body);
        $bodyHtml = nl2br(e($request->body));

        // Sla het bericht op
        $message = TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => $user->id,
            'from_email'  => config('services.microsoft.mailbox'),
            'from_name'   => $user->name,
            'direction'   => 'outbound',
            'subject'     => "Re: [{$ticket->ticket_number}] {$ticket->subject}",
            'body_html'   => $bodyHtml,
            'body_text'   => $bodyText,
            'sent_at'     => now(),
        ]);

        // Stuur de e-mail via Graph
        $html = view('emails.ticket-reply', [
            'ticket'  => $ticket,
            'message' => $message,
            'body'    => $bodyHtml,
            'agentName' => $user->name,
        ])->render();

        $payload = [
            'message' => [
                'subject' => "Re: [{$ticket->ticket_number}] {$ticket->subject}",
                'body'    => [
                    'contentType' => 'HTML',
                    'content'     => $html,
                ],
                'toRecipients' => [[
                    'emailAddress' => [
                        'address' => $ticket->customer->email,
                        'name'    => $ticket->customer->name,
                    ],
                ]],
            ],
            'saveToSentItems' => true,
        ];

        try {
            $graph->sendMail($payload);
        } catch (\Throwable $e) {
            Log::error('Reply versturen mislukt', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Bericht verstuurd.');
    }
}