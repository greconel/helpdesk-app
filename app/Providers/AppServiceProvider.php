<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Events\TicketAssigned;
use App\Listeners\SendTicketConfirmationToCustomer;
use App\Listeners\SendTicketAssignedToAgent;
use Illuminate\Support\Facades\Event;  // ← deze ontbreekt
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(TicketCreated::class, SendTicketConfirmationToCustomer::class);
        Event::listen(TicketAssigned::class, SendTicketAssignedToAgent::class);
    }
}