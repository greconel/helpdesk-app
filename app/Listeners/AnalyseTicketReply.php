<?php

namespace App\Listeners;

use App\Events\TicketReplyReceived;
use App\Services\TicketReplyAnalyser;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnalyseTicketReply implements ShouldQueue
{
    public int $tries   = 2;
    public int $backoff = 30;

    public function __construct(private TicketReplyAnalyser $analyser) {}

    public function handle(TicketReplyReceived $event): void
    {
        $this->analyser->analyse($event->ticket, $event->bodyText);
    }
}