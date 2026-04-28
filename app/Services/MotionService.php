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

    public function buildSupportProjectName(string $ticketNumber, string $customerName): string
    {
        $ticketNumber = ltrim($ticketNumber, '#');
        $normalizedTicketNumber = ctype_digit($ticketNumber)
            ? str_pad($ticketNumber, 4, '0', STR_PAD_LEFT)
            : $ticketNumber;

        return mb_substr("SUPPORT - [#{$normalizedTicketNumber}] {$customerName}", 0, 120);
    }

    public function buildSupportProjectDescription(
        string $ticketNumber,
        string $customerName,
        string $subject,
        ?string $impact = null,
        array $labels = [],
        ?string $description = null
    ): string {
        $ticketNumber = ltrim($ticketNumber, '#');
        $labelTekst = !empty($labels) ? implode(', ', $labels) : 'Geen labels';
        $impactTekst = $impact ? ucfirst($impact) : 'Niet opgegeven';
        $beschrijving = trim((string) $description);
        $beschrijving = $beschrijving !== '' ? $beschrijving : 'Geen beschrijving beschikbaar.';

        return implode("\n", [
            "Ticket: #{$ticketNumber}",
            "Klant: {$customerName}",
            "Onderwerp: {$subject}",
            "Impact: {$impactTekst}",
            "Labels: {$labelTekst}",
            '',
            'Beschrijving:',
            $beschrijving,
        ]);
    }

    public function createProjectFromTemplate(
        string $name,
        string $startDate,
        string $dueDate,
        ?string $description = null,
        ?string $developerMotionUserId = null
    ): ?string {
        try {
            $definitionId = config('services.motion.support_template_id');
            $stage1Id     = config('services.motion.support_stage_1_id');
            $stage2Id     = config('services.motion.support_stage_2_id');
            $projectManagerMotionUserId = config('services.motion.support_project_manager_id');

            if (!$stage1Id || !$stage2Id) {
                Log::warning('Motion stages niet ingesteld voor template-project', [
                    'support_stage_1_id_present' => (bool) $stage1Id,
                    'support_stage_2_id_present' => (bool) $stage2Id,
                ]);
                return null;
            }

            if (!$developerMotionUserId) {
                Log::warning('Motion template project kan niet worden aangemaakt zonder developer Motion user id', [
                    'project_manager_id_present' => (bool) $projectManagerMotionUserId,
                ]);
                return null;
            }

            if (!$projectManagerMotionUserId) {
                Log::warning('Motion template project kan niet worden aangemaakt zonder project manager Motion user id', [
                    'developer_id_present' => (bool) $developerMotionUserId,
                ]);
                return null;
            }

            $stage1Due = \Carbon\Carbon::parse($startDate)->addWeekdays(3)->format('Y-m-d');
            $stage2Due = \Carbon\Carbon::parse($stage1Due)->addWeekdays(1)->format('Y-m-d');

            $payload = [
                'name'                => $name,
                'workspaceId'         => $this->workspaceId,
                'projectDefinitionId' => $definitionId,
                'startDate'           => $startDate,
                'dueDate'             => $dueDate,
                'description'         => $description,
                'stages'              => [
                    [
                        'stageDefinitionId'  => $stage1Id,
                        'dueDate'            => $stage1Due,
                        'variableInstances'   => [
                            [
                                'variableName' => 'Developer',
                                'value'        => $developerMotionUserId,
                            ],
                            [
                                'variableName' => 'Project manager',
                                'value'        => $projectManagerMotionUserId,
                            ],
                        ],
                    ],
                    [
                        'stageDefinitionId'  => $stage2Id,
                        'dueDate'            => $stage2Due,
                        'variableInstances'   => [
                            [
                                'variableName' => 'Developer',
                                'value'        => $developerMotionUserId,
                            ],
                            [
                                'variableName' => 'Project manager',
                                'value'        => $projectManagerMotionUserId,
                            ],
                        ],
                    ],
                ],
            ];

            Log::info('Motion project payload', ['payload' => $payload]);

            $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
                ->post("{$this->baseUrl}/projects", $payload);

            if ($response->successful()) {
                Log::info('Motion project aangemaakt', ['body' => $response->body()]);
                return $response->json('project.id') ?? $response->json('id');
            }

            Log::error('Motion createProjectFromTemplate mislukt', [
                'response' => $response->body(),
                'status'   => $response->status(),
            ]);
            return null;

        } catch (\Throwable $e) {
            Log::error('Motion createProjectFromTemplate exception', ['message' => $e->getMessage()]);
            return null;
        }
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

            $labelTekst   = !empty($labels) ? implode(', ', $labels) : 'Geen labels';
            $impactTekst  = $impact ? ucfirst($impact) : 'Niet opgegeven';
            $beschrijving = trim(substr(strip_tags($description), 0, 600));
            $beschrijving = $beschrijving !== '' ? $beschrijving : 'Geen beschrijving beschikbaar.';

            $body = implode("\n", [
                '**[SUPPORT]**',
                "Impact: {$impactTekst}",
                "Labels: {$labelTekst}",
                '',
                'Beschrijving:',
                $beschrijving,
            ]);

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

            return $response->json('task') ?? $response->json();

        } catch (\Throwable $e) {
            Log::error('Motion getTask exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}