<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Mail\TicketConfirmation;
use Illuminate\Support\Facades\Mail;

class SendTicketConfirmationToCustomer
{
    public function handle(TicketCreated $event): void
    {
        Mail::to($event->ticket->customer->email)
            ->send(new TicketConfirmation($event->ticket));
    }
}