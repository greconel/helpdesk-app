<?php

namespace App\Observers;

use App\Models\AiCorrectionLog;
use App\Models\Ticket;
use App\Services\MotionService;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        // --- Bestaande Motion logica ---
        $motion = app(MotionService::class);

        $assigneeVeranderd = $ticket->wasChanged('assigned_to');
        $statusVeranderd   = $ticket->wasChanged('status');

        if ($statusVeranderd && $ticket->status === 'closed' && $ticket->motion_task_id) {
            $motion->completeTask($ticket->motion_task_id);
            // Ga door — we willen ook de correctielog nog checken
        }

        if ($assigneeVeranderd && $ticket->assigned_to && !$ticket->motion_task_id) {
            $agent = $ticket->agent;
            if ($agent?->motion_user_id) {
                $taskId = $motion->createTask(
                    $ticket->subject,
                    $ticket->description,
                    $agent->motion_user_id
                );
                if ($taskId) {
                    $ticket->updateQuietly(['motion_task_id' => $taskId]);
                }
            }
        }

        if ($assigneeVeranderd && $ticket->assigned_to && $ticket->motion_task_id) {
            $agent = $ticket->agent;
            if ($agent?->motion_user_id) {
                $motion->updateAssignee($ticket->motion_task_id, $agent->motion_user_id);
            }
        }

        // --- Nieuwe AI correctielog logica ---
        $this->detectAiCorrection($ticket);
    }

    private function detectAiCorrection(Ticket $ticket): void
    {
        $impactGewijzigd = $ticket->wasChanged('impact');
        $labelsGewijzigd = $ticket->wasChanged('ai_labelled_labels');

        // Niets gewijzigd dat relevant is
        if (!$impactGewijzigd && !$labelsGewijzigd) {
            return;
        }

        // Controleer of de wijziging een AI-label betreft.
        // We kijken naar de ORIGINELE waarden vóór de update.
        $originelen = $ticket->getOriginal();

        $wasAiImpact  = (bool) ($originelen['ai_labelled_impact'] ?? false);
        $wasAiLabels  = (bool) ($originelen['ai_labelled_labels'] ?? false);

        // Als geen van beide door AI was gelabeld, niets te loggen
        if (!$wasAiImpact && !$wasAiLabels) {
            return;
        }

        // Alleen loggen als de relevante AI-velden ook echt wijzigden
        $impactCorrectie = $impactGewijzigd && $wasAiImpact;
        $labelsCorrectie = $labelsGewijzigd && $wasAiLabels;

        if (!$impactCorrectie && !$labelsCorrectie) {
            return;
        }

        // Haal het originele AI-voorstel op uit de cache
        $aiAnalysis = cache("ai_analysis_{$ticket->id}");

        if (!$aiAnalysis) {
            // Cache is verlopen — we loggen wat we weten maar zonder AI-voorstel
            Log::info("AI correctielog: cache verlopen voor ticket {$ticket->ticket_number}, log zonder AI-voorstel.");
        }

        // Bepaal het type correctie
        $type = match (true) {
            $impactCorrectie && $labelsCorrectie => 'both',
            $impactCorrectie                     => 'impact_only',
            default                              => 'labels_only',
        };

        // Huidige labels van het ticket ophalen
        $agentLabels = $ticket->labels()->pluck('name')->toArray();

        AiCorrectionLog::create([
            'ticket_id'                  => $ticket->id,
            'user_id'                    => auth()->id(),
            'ai_impact'                  => $aiAnalysis['impact'] ?? $originelen['impact'],
            'ai_labels'                  => $aiAnalysis['labels'] ?? [],
            'ai_skill_version'           => $aiAnalysis['skill_version'] ?? 'onbekend',
            'agent_impact'               => $ticket->impact,
            'agent_labels'               => $agentLabels,
            'ticket_subject'             => $ticket->subject,
            'ticket_description_snippet' => substr(strip_tags($ticket->description), 0, 500),
            'correction_type'            => $type,
        ]);

        Log::info("AI correctie gelogd voor ticket {$ticket->ticket_number}", [
            'type'        => $type,
            'ai_impact'   => $aiAnalysis['impact'] ?? null,
            'agent_impact'=> $ticket->impact,
        ]);
    }
}