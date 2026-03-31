<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\AnalyseTicketJob;

class AnalyseTicketWithAI
{
    public function handle(TicketCreated $event): void
    {
        AnalyseTicketJob::dispatch($event->ticket);
    }
}