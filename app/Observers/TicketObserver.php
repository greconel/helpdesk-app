<?php

namespace App\Observers;

use App\Events\AiCorrectionLogCreated;
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

        // 1. Ticket gesloten → Motion project afronden
        if ($statusChanged && $ticket->status === 'closed' && $ticket->motion_task_id) {
            $ticket->loadMissing('timeLogs');
            $totaalMinuten = $ticket->timeLogs->sum('duration_minutes');
            if ($totaalMinuten > 0) {
                $motion->updateDuration($ticket->motion_task_id, $totaalMinuten);
            }
            $motion->completeTask($ticket->motion_task_id);
            return;
        }

        // 2. Agent toegewezen + nog geen project → project aanmaken via template
        if ($assigneeChanged && $ticket->assigned_to && !$ticket->motion_task_id) {
            $agent = $ticket->agent;
            if ($agent?->motion_user_id) {
                $ticket->loadMissing('customer');

                $projectName = $motion->buildSupportProjectName(
                    (string) $ticket->ticket_number,
                    $ticket->customer->name,
                );
                $projectDescription = $motion->buildSupportProjectDescription(
                    (string) $ticket->ticket_number,
                    $ticket->customer->name,
                    $ticket->subject,
                    $ticket->impact,
                    $ticket->labels()->pluck('name')->toArray(),
                    mb_substr(strip_tags($ticket->description), 0, 600),
                );

                $startDate   = now()->format('Y-m-d');
                $dueDate     = \Carbon\Carbon::now()->addWeekdays(4)->format('Y-m-d');

                $projectId = $motion->createProjectFromTemplate(
                    name:                  $projectName,
                    startDate:             $startDate,
                    dueDate:               $dueDate,
                    description:           $projectDescription,
                    developerMotionUserId: $agent->motion_user_id,
                );

                if ($projectId) {
                    $ticket->updateQuietly(['motion_task_id' => $projectId]);
                    Log::info("Motion project aangemaakt voor ticket {$ticket->ticket_number}", [
                        'project_id' => $projectId,
                    ]);
                } else {
                    Log::warning("Motion project aanmaken mislukt voor ticket {$ticket->ticket_number}");
                }
            } else {
                Log::warning("Ticket {$ticket->ticket_number}: agent heeft geen motion_user_id — project niet aangemaakt.");
            }
            return;
        }

        // 3. Agent gewijzigd + project bestaat al → assignee updaten
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

        if (! $impactGewijzigd && ! $labelsGewijzigd) {
            return;
        }

        $originelen = $ticket->getOriginal();

        $wasAiImpact = (bool) ($originelen['ai_labelled_impact'] ?? false);
        $wasAiLabels = (bool) ($originelen['ai_labelled_labels'] ?? false);

        if (! $wasAiImpact && ! $wasAiLabels) {
            return;
        }

        $impactCorrectie = $impactGewijzigd && $wasAiImpact;
        $labelsCorrectie = $labelsGewijzigd && $wasAiLabels;

        if (! $impactCorrectie && ! $labelsCorrectie) {
            return;
        }

        $dbAnalysis = AiAnalysis::where('ticket_id', $ticket->id)->first();

        $aiAnalysis = $dbAnalysis ? [
            'impact'        => $dbAnalysis->impact,
            'labels'        => $dbAnalysis->labels,
            'skill_version' => $dbAnalysis->skill_version,
        ] : null;

        if (! $aiAnalysis) {
            Log::info("AI correctielog: geen AI-voorstel gevonden voor ticket {$ticket->ticket_number}.");
        }

        $type = match (true) {
            $impactCorrectie && $labelsCorrectie => 'both',
            $impactCorrectie                     => 'impact_only',
            default                              => 'labels_only',
        };

        $agentLabels = $ticket->labels()->pluck('name')->toArray();

        $log = AiCorrectionLog::create([
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

        AiCorrectionLogCreated::dispatch($log);

        Log::info("AI correctie gelogd voor ticket {$ticket->ticket_number}", [
            'type'         => $type,
            'ai_impact'    => $aiAnalysis['impact'] ?? null,
            'agent_impact' => $ticket->impact,
        ]);
    }
}