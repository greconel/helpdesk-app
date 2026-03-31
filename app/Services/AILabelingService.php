<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AILabelingService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-5-20250929';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    public function analyse(string $subject, string $description): ?array
    {
        $prompt = $this->buildPrompt($subject, $description);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 100,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Anthropic API call mislukt', ['body' => $response->body()]);
                return null;
            }

            $content = $response->json('content.0.text');

            // Verwijder markdown code blocks indien aanwezig
            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```\s*/i', '', $content);
            $content = trim($content);

            $result = json_decode($content, true);

            if (!$this->isValid($result)) {
                Log::warning('Ongeldig AI resultaat', ['result' => $result]);
                return null;
            }

            return $result;

        } catch (\Throwable $e) {
            Log::error('AILabellingService exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function buildPrompt(string $subject, string $description): string
    {
        $cleanDescription = strip_tags($description);

        return <<<PROMPT
Je bent een helpdesk assistent voor een software bedrijf.
Analyseer het volgende ticket en geef een label en impactwaarde terug.

Onderwerp: {$subject}
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