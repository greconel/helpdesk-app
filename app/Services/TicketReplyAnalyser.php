<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketReplyAnalyser
{
    /**
     * Analyseer een inkomende reply en voer automatisch een actie uit
     * op het ticket als de AI daartoe besluit.
     */
    public function analyse(Ticket $ticket, string $bodyText): void
    {
        if ($ticket->status === 'closed') {
            return;
        }

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 100,
            'messages'   => [[
                'role'    => 'user',
                'content' => $this->buildPrompt($bodyText),
            ]],
        ]);

        if (! $response->successful()) {
            Log::warning("TicketReplyAnalyser: API mislukt voor ticket {$ticket->ticket_number}", [
                'body' => $response->body(),
            ]);
            return;
        }

        $content = trim($response->json('content.0.text'));
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*/i', '', $content);
        $content = trim($content);

        $result = json_decode($content, true);

        if (! $result || ! isset($result['action'])) {
            return;
        }

        match ($result['action']) {
            'close'    => $ticket->update([
                'status'    => 'closed',
                'closed_at' => now(),
            ]),
            'escalate' => $ticket->update(['impact' => 'high']),
            default    => null,
        };

        if ($result['action'] !== 'nothing') {
            Log::info("TicketReplyAnalyser: actie uitgevoerd voor ticket {$ticket->ticket_number}", [
                'action' => $result['action'],
                'reden'  => $result['reason'] ?? '—',
            ]);
        }
    }

    private function buildPrompt(string $bodyText): string
    {
        $tekst = substr($bodyText, 0, 500);

        return <<<PROMPT
Analyseer de volgende e-mail reply van een klant aan een helpdesk.

Bepaal welke actie het systeem moet ondernemen op basis van de inhoud.

E-mail:
"{$tekst}"

Mogelijke acties:
- "close": de klant geeft aan dat het probleem opgelost is, ze er geen hulp meer bij nodig hebben, of ze het verzoek intrekken
- "escalate": de klant is gefrustreerd, het probleem is dringender geworden, of ze dreigen te escaleren
- "nothing": gewone reactie, vraag om info, of onduidelijk

Antwoord ALLEEN in dit JSON formaat:
{
  "action": "close",
  "reason": "Klant geeft aan dat het probleem opgelost is"
}
PROMPT;
    }
}