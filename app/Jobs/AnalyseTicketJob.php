<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\AILabellingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnalyseTicketJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Ticket $ticket) {}

    public function handle(AILabellingService $ai): void
    {
        // Skip als agent al manueel labels én impact heeft ingesteld
        if ($this->ticket->impact && $this->ticket->labels()->exists()) {
            Log::info("Ticket {$this->ticket->ticket_number}: AI analyse overgeslagen, al manueel ingesteld.");
            return;
        }

        $result = $ai->analyse(
            $this->ticket->subject,
            $this->ticket->description
        );

        if (!$result) {
            Log::warning("Ticket {$this->ticket->ticket_number}: AI analyse gaf geen resultaat.");
            return;
        }

        // Alleen overschrijven wat nog niet ingesteld is
        if (!$this->ticket->impact && !empty($result['impact'])) {
            $this->ticket->update(['impact' => $result['impact']]);
        }

        if (!$this->ticket->labels()->exists() && !empty($result['labels'])) {
            $labels = \App\Models\Label::whereIn('name', $result['labels'])->pluck('id');
            $this->ticket->labels()->sync($labels);
        }

        Log::info("Ticket {$this->ticket->ticket_number}: AI analyse succesvol.", $result);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("AnalyseTicketJob mislukt voor ticket {$this->ticket->ticket_number}", [
            'error' => $e->getMessage(),
        ]);
    }
}