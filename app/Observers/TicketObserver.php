<?php

namespace App\Observers;

use App\Models\AiAnalysis;
use App\Models\AiCorrectionLog;
use App\Models\Ticket;
use App\Services\MotionService;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        $this->detectAiCorrection($ticket);
        $this->handleMotionIntegration($ticket);
    }

    private function handleMotionIntegration(Ticket $ticket): void
    {
        $motion = app(MotionService::class);

        $assigneeChanged = $ticket->wasChanged('assigned_to');
        $statusChanged   = $ticket->wasChanged('status');

        // Ticket gesloten → tijd updaten en taak afronden in Motion
        if ($statusChanged && $ticket->status === 'closed' && $ticket->motion_task_id) {
            $ticket->loadMissing('timeLogs');
            $totaalMinuten = $ticket->timeLogs->sum('duration_minutes');
            if ($totaalMinuten > 0) {
                $motion->updateDuration($ticket->motion_task_id, $totaalMinuten);
            }
            $motion->completeTask($ticket->motion_task_id);
            return;
        }

        // Nieuwe toewijzing aan agent → maak Motion taak aan (alleen als nog geen taak bestaat)
        if ($assigneeChanged && $ticket->assigned_to && !$ticket->motion_task_id) {
            $agent = $ticket->agent;
            if ($agent?->motion_user_id) {
                $projectId = $ticket->customer?->motion_project_id;
                $taskId = $motion->createTask(
                    "[{$ticket->ticket_number}] {$ticket->subject}",
                    $ticket->description,
                    $agent->motion_user_id,
                    $projectId,
                    $ticket->impact,
                    $ticket->labels->pluck('name')->toArray()
                );
                if ($taskId) {
                    $ticket->updateQuietly(['motion_task_id' => $taskId]);
                }
            }
            return;
        }

        // Agent gewijzigd op bestaande taak → update assignee in Motion
        if ($assigneeChanged && $ticket->assigned_to && $ticket->motion_task_id) {
            $agent = $ticket->agent;
            if ($agent?->motion_user_id) {
                $motion->updateAssignee($ticket->motion_task_id, $agent->motion_user_id);
            }
        }
    }

    private function detectAiCorrection(Ticket $ticket): void
    {
        $impactGewijzigd = $ticket->wasChanged('impact');
        $labelsGewijzigd = $ticket->wasChanged('ai_labelled_labels');

        if (!$impactGewijzigd && !$labelsGewijzigd) {
            return;
        }

        $originelen = $ticket->getOriginal();

        $wasAiImpact = (bool) ($originelen['ai_labelled_impact'] ?? false);
        $wasAiLabels = (bool) ($originelen['ai_labelled_labels'] ?? false);

        if (!$wasAiImpact && !$wasAiLabels) {
            return;
        }

        $impactCorrectie = $impactGewijzigd && $wasAiImpact;
        $labelsCorrectie = $labelsGewijzigd && $wasAiLabels;

        if (!$impactCorrectie && !$labelsCorrectie) {
            return;
        }

        $dbAnalysis = AiAnalysis::where('ticket_id', $ticket->id)->first();

        $aiAnalysis = $dbAnalysis ? [
            'impact'        => $dbAnalysis->impact,
            'labels'        => $dbAnalysis->labels,
            'skill_version' => $dbAnalysis->skill_version,
        ] : null;

        if (!$aiAnalysis) {
            Log::info("AI correctielog: geen AI-voorstel gevonden voor ticket {$ticket->ticket_number}.");
        }

        $type = match (true) {
            $impactCorrectie && $labelsCorrectie => 'both',
            $impactCorrectie                     => 'impact_only',
            default                              => 'labels_only',
        };

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
            'type'         => $type,
            'ai_impact'    => $aiAnalysis['impact'] ?? null,
            'agent_impact' => $ticket->impact,
        ]);
    }
}