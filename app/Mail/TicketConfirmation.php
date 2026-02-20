<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketConfirmation extends Mailable
{
    public function __construct(
        public Ticket $ticket
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Uw ticket {$this->ticket->ticket_number} is aangemaakt",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticket-confirmation',
        );
    }
}