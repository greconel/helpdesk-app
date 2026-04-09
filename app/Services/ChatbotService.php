<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-5-20250929';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    public function answer(string $question, string $sessionToken): string
    {
        // 1. Haal gespreksgeschiedenis op
        $history = $this->getHistory($sessionToken);

        // 2. Zoek relevante documentatie op
        $chunks = $this->findRelevantChunks($question);

        // 3. Bouw de context op vanuit de docs
        $context = $this->buildContext($chunks);

        // 4. Stuur naar Claude
        $answer = $this->askClaude($question, $history, $context);

        // 5. Sla op in gespreksgeschiedenis
        $this->saveToHistory($sessionToken, $question, $answer);

        return $answer;
    }

    private function findRelevantChunks(string $question): Collection
    {
        // Haal betekenisvolle woorden op uit de vraag
        $keywords = collect(explode(' ', strtolower($question)))
            ->map(fn($word) => trim($word, '.,?!'))
            ->filter(fn($word) => strlen($word) > 3)
            ->take(5);

        // Als er geen bruikbare keywords zijn
        // geef alle docs terug
        if ($keywords->isEmpty()) {
            return KnowledgeChunk::limit(4)->get();
        }

        // Bereken een score per document
        // op basis van hoeveel keywords matchen
        $chunks = KnowledgeChunk::all();

        return $chunks->map(function($chunk) use ($keywords) {
            $score = 0;
            $contentLower = strtolower($chunk->content);
            $titleLower   = strtolower($chunk->title);

            foreach ($keywords as $keyword) {
                // Titel match telt zwaarder dan content match
                if (str_contains($titleLower, $keyword)) {
                    $score += 3;
                }
                if (str_contains($contentLower, $keyword)) {
                    $score += 1;
                }
            }

            $chunk->relevance_score = $score;
            return $chunk;
        })
        ->filter(fn($chunk) => $chunk->relevance_score > 0)
        ->sortByDesc('relevance_score')
        ->take(4)
        ->values();
    }

    private function buildContext(Collection $chunks): string
    {
        if ($chunks->isEmpty()) {
            return 'Geen relevante documentatie gevonden.';
        }

        return $chunks->map(fn($chunk) =>
            "### {$chunk->title}\n\n{$chunk->content}"
        )->join("\n\n---\n\n");
    }

    private function askClaude(
        string $question,
        array $history,
        string $context
    ): string {
        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 500,
            'system'     => "Je bent een behulpzame assistent voor een helpdesk systeem.
Beantwoord vragen op basis van de documentatie hieronder.
Antwoord altijd in het Nederlands.
Wees beknopt en duidelijk.
Als het antwoord niet in de documentatie staat, zeg dan eerlijk:
'Ik heb hier geen antwoord op. Ik stel voor om een ticket aan te maken.'
Verzin nooit informatie die niet in de documentatie staat.

DOCUMENTATIE:
{$context}",
            'messages' => array_merge(
                $history,
                [
                    ['role' => 'user', 'content' => $question]
                ]
            ),
        ]);

        if (!$response->successful()) {
            Log::error('ChatbotService: Claude API call mislukt', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return 'Er ging iets mis. Probeer het opnieuw.';
        }

        return $response->json('content.0.text');
    }

    private function getHistory(string $sessionToken): array
    {
        $session = ChatSession::where('session_token', $sessionToken)
            ->first();

        return $session?->messages ?? [];
    }

    private function saveToHistory(
        string $sessionToken,
        string $question,
        string $answer
    ): void {
        $session = ChatSession::firstOrCreate(
            ['session_token' => $sessionToken],
            ['messages'      => []]
        );

        $messages   = $session->messages ?? [];
        $messages[] = ['role' => 'user',      'content' => $question];
        $messages[] = ['role' => 'assistant', 'content' => $answer];

        // Maximaal 10 berichten bijhouden
        if (count($messages) > 10) {
            $messages = array_slice($messages, -10);
        }

        $session->update(['messages' => $messages]);
    }
}