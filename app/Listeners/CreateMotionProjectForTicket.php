<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Services\MotionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateMotionProjectForTicket implements ShouldQueue
{
    public int $tries   = 3;
    public int $backoff = 30;

    public function handle(TicketCreated $event): void
    {
        $ticket     = $event->ticket;
        $motion     = app(MotionService::class);
        $templateId = config('services.motion.support_template_id');

        if (!$templateId) {
            Log::warning('MOTION_SUPPORT_TEMPLATE_ID niet ingesteld — project aanmaken overgeslagen.');
            return;
        }

        $ticket->loadMissing(['customer', 'agent']);

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

        // Developer meegeven als er al een agent is bij aanmaken
        $developerMotionUserId = $ticket->agent?->motion_user_id ?? null;

        $projectId = $motion->createProjectFromTemplate(
            name:                  $projectName,
            startDate:             $startDate,
            dueDate:               $dueDate,
            description:           $projectDescription,
            developerMotionUserId: $developerMotionUserId,
        );

        if ($projectId) {
            $ticket->updateQuietly(['motion_task_id' => $projectId]);
            Log::info("Motion project aangemaakt voor ticket {$ticket->ticket_number}", [
                'project_id' => $projectId,
            ]);
        } else {
            Log::warning("Motion project aanmaken mislukt voor ticket {$ticket->ticket_number}");
        }
    }
}