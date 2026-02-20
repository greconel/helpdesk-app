<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketAssigned extends Mailable
{
    public function __construct(
        public Ticket $ticket
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket {$this->ticket->ticket_number} aan jou toegewezen",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticket-assigned',
        );
    }
}