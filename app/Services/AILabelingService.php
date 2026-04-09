<?php

namespace App\Services;

use App\Models\AiAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-service voor ticket analyse via Claude API
 * Wijst automatisch impact (low/medium/high) en labels toe
 */
class AILabelingService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-5-20250929';
    private string $skillVersion = 'v1.0';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    /**
     * Analyseer ticket en retourneer impact + labels
     *
     * @param string $subject Ticket onderwerp
     * @param string $description Ticket beschrijving
     * @param int $ticketId Ticket ID (0 = niet opslaan)
     * @return array|null ['impact' => ..., 'labels' => ...] of null bij fout
     */
    public function analyse(string $subject, string $description, int $ticketId): ?array
    {
        $skill  = $this->loadSkill();
        $prompt = $this->buildPrompt($subject, $description, $skill);

        try {
            // Call Anthropic API
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 200,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Anthropic API call mislukt', ['body' => $response->body()]);
                return null;
            }

            // Parse JSON response (strip markdown code blocks)
            $content = $response->json('content.0.text');
            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```\s*/i', '', $content);
            $content = trim($content);

            $result = json_decode($content, true);

            if (!$this->isValid($result)) {
                Log::warning('Ongeldig AI resultaat', ['result' => $result]);
                return null;
            }

            // Sla result op in database
            if ($ticketId > 0) {
                AiAnalysis::updateOrCreate(
                    ['ticket_id' => $ticketId],
                    [
                        'impact'        => $result['impact'],
                        'labels'        => $result['labels'],
                        'skill_version' => $this->skillVersion,
                    ]
                );
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('AILabelingService exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Laad business rules (skill) uit storage
     */
    private function loadSkill(): string
    {
        $path = storage_path('ai-skill/labeling-skill.md');

        if (!file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path);

        // Extract versie nummer
        if (preg_match('/\*\*Versie:\*\*\s*(.+)/m', $content, $matches)) {
            $this->skillVersion = trim($matches[1]);
        }

        return $content;
    }

    /**
     * Bouw prompt voor ticket analyse
     */
    private function buildPrompt(string $subject, string $description, string $skill): string
    {
        $cleanDescription = substr(strip_tags($description), 0, 800);

        $skillSection = '';
        if ($skill) {
            $skillSection = "## Jouw opgebouwde kennis en regels\n\n{$skill}\n\n---\n\n";
        }

        return <<<PROMPT
Je bent een helpdesk assistent voor een software bedrijf.
Analyseer het volgende ticket en geef een label en impactwaarde terug.

{$skillSection}Onderwerp: {$subject}
Beschrijving: {$cleanDescription}

Kies één of meerdere labels uit deze lijst:
- "bug": een fout of defect in de software
- "feature request": de klant vraagt om een nieuwe functionaliteit
- "onderzoek": het probleem is onduidelijk of moet verder onderzocht worden
- "eigenlijk niet voor ons": het probleem ligt buiten onze verantwoordelijkheid

Kies één impactwaarde:
- "low": de klant kan verder werken, het is een kleine hinder
- "medium": de klant ondervindt hinder maar heeft een workaround
- "high": de klant kan niet verder werken, productie ligt stil

Antwoord ALLEEN in dit JSON formaat, niets anders:
{
  "labels": ["bug"],
  "impact": "high"
}

Als je onvoldoende informatie hebt, geef dan terug:
{
  "labels": [],
  "impact": null
}
PROMPT;
    }

    /**
     * Valideer AI response
     */
    private function isValid(?array $result): bool
    {
        if (!$result) return false;
        if (!isset($result['labels']) || !is_array($result['labels'])) return false;
        if (!array_key_exists('impact', $result)) return false;

        $validImpacts = ['low', 'medium', 'high', null];
        if (!in_array($result['impact'], $validImpacts, true)) return false;

        $validLabels = ['bug', 'feature request', 'onderzoek', 'eigenlijk niet voor ons'];
        foreach ($result['labels'] as $label) {
            if (!in_array($label, $validLabels, true)) return false;
        }

        return true;
    }
}