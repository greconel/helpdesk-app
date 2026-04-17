<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MotionService
{
    private string $apiKey;
    private string $workspaceId;
    private string $baseUrl = 'https://api.usemotion.com/v1';

    public function __construct()
    {
        $this->apiKey      = config('services.motion.key');
        $this->workspaceId = config('services.motion.workspace_id');
    }

    public function createTask(string $title, string $description, string $motionUserId, ?string $projectId = null, ?string $impact = null, array $labels = []): ?string
    {
        try {
            $motionPriority = match($impact) {
                'high'   => 'ASAP',
                'medium' => 'HIGH',
                'low'    => 'MEDIUM',
                default  => 'MEDIUM',
            };

            $labelTekst = !empty($labels) ? implode(', ', $labels) : '—';
            $impactTekst = $impact ? ucfirst($impact) : '—';

            $beschrijving = substr(strip_tags($description), 0, 300);

            $body = "**[SUPPORT]** · Impact: {$impactTekst} · Labels: {$labelTekst}

    {$beschrijving}";

            $payload = [
                'name'        => $title,
                'description' => $body,
                'workspaceId' => $this->workspaceId,
                'assigneeId'  => $motionUserId,
                'priority'    => $motionPriority,
                'status'      => 'Todo',
            ];

            if ($projectId) {
                $payload['projectId'] = $projectId;
            }

            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/tasks", $payload);

            if ($response->successful()) {
                return $response->json('task.id') ?? $response->json('id');
            }

            Log::error('Motion createTask mislukt', ['response' => $response->body()]);
            return null;

        } catch (\Throwable $e) {
            Log::error('Motion createTask exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function updateAssignee(string $motionTaskId, string $motionUserId): void
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->patch("{$this->baseUrl}/tasks/{$motionTaskId}", [
                    'assigneeId' => $motionUserId,
                ]);

            if (!$response->successful()) {
                Log::error('Motion updateAssignee mislukt', ['response' => $response->body()]);
            }

        } catch (\Throwable $e) {
            Log::error('Motion updateAssignee exception', ['message' => $e->getMessage()]);
        }
    }

    public function completeTask(string $motionTaskId): void
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->patch("{$this->baseUrl}/tasks/{$motionTaskId}", [
                    'status' => 'Completed',
                ]);

            if (!$response->successful()) {
                Log::error('Motion completeTask mislukt', ['response' => $response->body()]);
            }

        } catch (\Throwable $e) {
            Log::error('Motion completeTask exception', ['message' => $e->getMessage()]);
        }
    }
    public function updateDuration(string $motionTaskId, int $minutes): void
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->patch("{$this->baseUrl}/tasks/{$motionTaskId}", [
                    'duration' => $minutes,
                ]);

            if (!$response->successful()) {
                Log::error('Motion updateDuration mislukt', ['response' => $response->body()]);
            }

        } catch (\Throwable $e) {
            Log::error('Motion updateDuration exception', ['message' => $e->getMessage()]);
        }
    }
    public function getProjects(): array
    {
        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->get("{$this->baseUrl}/projects", [
                'workspaceId' => $this->workspaceId,
            ]);

        if (!$response->successful()) {
            Log::error('Motion getProjects mislukt', ['body' => $response->body()]);
            return [];
        }

        return $response->json('projects', []);
    }
    public function getTask(string $motionTaskId): ?array
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->get("{$this->baseUrl}/tasks/{$motionTaskId}");

            if ($response->status() === 404) {
                return null;
            }

            if (!$response->successful()) {
                Log::error('Motion getTask mislukt', [
                    'task_id' => $motionTaskId,
                    'status'  => $response->status(),
                ]);
                return null;
            }

            // De API geeft de taak direct terug, of genest onder 'task'
            return $response->json('task') ?? $response->json();

        } catch (\Throwable $e) {
            Log::error('Motion getTask exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}