<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class TicketAssigned
{
    use Dispatchable;

    public function __construct(
        public Ticket $ticket,
        public User $agent
    ) {}
}