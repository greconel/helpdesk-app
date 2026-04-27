<?php

namespace App\Providers;

use App\Events\AiCorrectionLogCreated;
use App\Events\TicketReplyReceived;
use App\Models\Ticket;
use App\Observers\TicketObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);
    }
}