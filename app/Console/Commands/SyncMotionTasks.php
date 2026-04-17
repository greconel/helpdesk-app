<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\MotionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMotionTasks extends Command
{
    protected $signature   = 'motion:sync {--dry-run : Toon wijzigingen zonder op te slaan}';
    protected $description = 'Synchroniseer Motion taakstatussen naar tickets';

    public function handle(MotionService $motion): int
    {
        // Haal alle open tickets op met een Motion taak-koppeling
        $tickets = Ticket::whereNotNull('motion_task_id')
            ->whereNotIn('status', ['closed'])
            ->with(['agent'])
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('Geen tickets met Motion taken gevonden.');
            return self::SUCCESS;
        }

        $this->info("📋 {$tickets->count()} ticket(s) controleren...");

        $synced    = 0;
        $closed    = 0;
        $reassigned = 0;
        $notFound  = 0;

        foreach ($tickets as $ticket) {
            $task = $motion->getTask($ticket->motion_task_id);

            // Taak bestaat niet meer in Motion
            if ($task === null) {
                $notFound++;
                $this->line("  ⚠ {$ticket->ticket_number}: taak niet gevonden in Motion (ID: {$ticket->motion_task_id})");
                continue;
            }

            $changed = false;

            // 1. Taak afgerond in Motion → ticket sluiten
            if (($task['completed'] ?? false) === true && $ticket->status !== 'closed') {
                if ($this->option('dry-run')) {
                    $this->line("  [dry-run] {$ticket->ticket_number}: zou worden gesloten");
                } else {
                    $ticket->update([
                        'status'    => 'closed',
                        'closed_at' => now(),
                    ]);
                    $this->info("  ✓ {$ticket->ticket_number}: gesloten (taak afgerond in Motion)");
                    $closed++;
                }
                $changed = true;
            }

            // 2. Assignee gewijzigd in Motion → agent bijwerken
            $motionAssigneeId = data_get($task, 'assignees.0.id')
                             ?? data_get($task, 'assignee.id');

            if ($motionAssigneeId && !$changed) {
                $currentAgentMotionId = $ticket->agent?->motion_user_id;

                if ($motionAssigneeId !== $currentAgentMotionId) {
                    // Zoek de agent op basis van motion_user_id
                    $newAgent = \App\Models\User::where('motion_user_id', $motionAssigneeId)->first();

                    if ($newAgent) {
                        if ($this->option('dry-run')) {
                            $this->line("  [dry-run] {$ticket->ticket_number}: agent zou worden {$newAgent->name}");
                        } else {
                            $ticket->update(['assigned_to' => $newAgent->id]);
                            $this->info("  ✓ {$ticket->ticket_number}: agent bijgewerkt naar {$newAgent->name}");
                            $reassigned++;
                        }
                    }
                }
            }

            $synced++;
        }

        $this->newLine();
        $this->info("Klaar. Gecontroleerd: {$synced} | Gesloten: {$closed} | Herassigned: {$reassigned} | Niet gevonden: {$notFound}");

        Log::info('Motion sync voltooid', compact('synced', 'closed', 'reassigned', 'notFound'));

        return self::SUCCESS;
    }
}