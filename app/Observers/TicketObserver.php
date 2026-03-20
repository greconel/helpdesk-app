<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\MotionService;

class TicketObserver
{
    public function updated(Ticket $ticket): void
    {
        $motion = app(MotionService::class);

        $assigneeVeranderd = $ticket->wasChanged('assigned_to');
        $statusVeranderd   = $ticket->wasChanged('status');

        // Ticket gesloten → taak afsluiten
        if ($statusVeranderd && $ticket->status === 'closed' && $ticket->motion_task_id) {
            $motion->completeTask($ticket->motion_task_id);
            return;
        }

        // Nieuwe toewijzing → taak aanmaken
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
            return;
        }

        // Hertoewijzing → assignee updaten
        if ($assigneeVeranderd && $ticket->assigned_to && $ticket->motion_task_id) {
            $agent = $ticket->agent;
            if ($agent?->motion_user_id) {
                $motion->updateAssignee($ticket->motion_task_id, $agent->motion_user_id);
            }
        }
    }
}