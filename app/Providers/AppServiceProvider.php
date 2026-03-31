<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Ticket;
use App\Observers\TicketObserver;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\TicketCreated::class,
            \App\Listeners\AnalyseTicketWithAI::class,
        );
         \Illuminate\Support\Facades\Event::listen(
            \App\Events\TicketCreated::class,
            \App\Listeners\SendTicketConfirmationToCustomer::class,
        );
    }
}