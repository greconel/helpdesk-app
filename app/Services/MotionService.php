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

    public function createTask(string $title, string $description, string $motionUserId): ?string
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/tasks", [
                    'name'        => $title,
                    'description' => $description,
                    'workspaceId' => $this->workspaceId,
                    'assigneeId'  => $motionUserId,
                    'status'      => 'Todo',
                ]);

            if ($response->successful()) {
                return $response->json('task.id');
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
}