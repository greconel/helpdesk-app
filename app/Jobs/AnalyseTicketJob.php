<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\AILabelingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Async job voor AI-analyse van tickets
 * Wijst automatisch impact en labels toe, tenzij al manueel ingesteld
 */
class AnalyseTicketJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Ticket $ticket) {}

    /**
     * Analyseer ticket en wijs impact/labels toe
     */
    public function handle(AILabelingService $ai): void
    {
        // Skip als al manueel ingesteld
        if ($this->ticket->impact && $this->ticket->labels()->exists()) {
            Log::info("Ticket {$this->ticket->ticket_number}: AI analyse overgeslagen, al manueel ingesteld.");
            return;
        }

        $result = $ai->analyse(
            $this->ticket->subject,
            $this->ticket->description,
            $this->ticket->id
        );

        if (!$result) {
            Log::warning("Ticket {$this->ticket->ticket_number}: AI analyse gaf geen resultaat.");
            return;
        }

        // Wijs impact toe als nog niet ingesteld
        if (!$this->ticket->impact && !empty($result['impact'])) {
            $this->ticket->update([
                'impact'             => $result['impact'],
                'ai_labelled_impact' => true,
            ]);
        }

        // Wijs labels toe als nog geen labels aanwezig
        if (!$this->ticket->labels()->exists() && !empty($result['labels'])) {
            $labels = \App\Models\Label::whereIn('name', $result['labels'])->pluck('id');
            $this->ticket->labels()->sync($labels);
            $this->ticket->update(['ai_labelled_labels' => true]);
        }

        Log::info("Ticket {$this->ticket->ticket_number}: AI analyse succesvol.", $result);
    }

    /**
     * Behandel mislukking na alle retry-pogingen
     */
    public function failed(\Throwable $e): void
    {
        Log::error("AnalyseTicketJob mislukt voor ticket {$this->ticket->ticket_number}", [
            'error' => $e->getMessage(),
        ]);
    }
}
