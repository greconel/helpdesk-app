<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Mail\TicketAssigned as TicketAssignedMail;
use Illuminate\Support\Facades\Mail;

class SendTicketAssignedToAgent
{
    public function handle(TicketAssigned $event): void
    {
        Mail::to($event->ticket->agent->email)
            ->queue(new TicketAssignedMail($event->ticket));
    }
}