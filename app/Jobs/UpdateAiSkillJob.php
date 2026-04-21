<?php

namespace App\Jobs;

use App\Models\AiCorrectionLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateAiSkillJob implements ShouldQueue
{
    use Queueable;

    public int $tries    = 2;
    public int $timeout  = 120;

    public function handle(): void
    {
        $corrections = AiCorrectionLog::where('processed', false)
            ->where('ignore_in_training', false)
            ->with(['ticket', 'agent'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($corrections->isEmpty()) {
            Log::info('UpdateAiSkillJob: geen onverwerkte correcties gevonden.');
            return;
        }

        $skillPath    = storage_path('ai-skill/labeling-skill.md');
        $currentSkill = file_exists($skillPath)
            ? file_get_contents($skillPath)
            : $this->defaultSkill();

        $correctionText = $this->formatCorrections($corrections);

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(90)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 2000,
            'messages'   => [[
                'role'    => 'user',
                'content' => $this->buildUpdatePrompt($currentSkill, $correctionText, $corrections->count()),
            ]],
        ]);

        if (! $response->successful()) {
            Log::error('UpdateAiSkillJob: Anthropic API call mislukt', ['body' => $response->body()]);
            throw new \RuntimeException('Anthropic API call mislukt: ' . $response->status());
        }

        $newSkill = $response->json('content.0.text');
        $newSkill = preg_replace('/^```markdown\s*/im', '', $newSkill);
        $newSkill = preg_replace('/^```\s*/im', '', $newSkill);
        $newSkill = trim($newSkill);

        $this->makeBackup($skillPath, $currentSkill);

        if (! is_dir(dirname($skillPath))) {
            mkdir(dirname($skillPath), 0755, true);
        }

        file_put_contents($skillPath, $newSkill);

        AiCorrectionLog::whereIn('id', $corrections->pluck('id'))->update([
            'processed' => true,
        ]);

        Log::info('UpdateAiSkillJob: skill bijgewerkt', [
            'corrections_verwerkt' => $corrections->count(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('UpdateAiSkillJob mislukt', ['error' => $e->getMessage()]);
    }

    // ─── private helpers (zelfde logica als het Artisan command) ───────────

    private function formatCorrections($corrections): string
    {
        return $corrections->map(function (AiCorrectionLog $log) {
            $aiLabels    = implode(', ', $log->ai_labels ?? []);
            $agentLabels = implode(', ', $log->agent_labels ?? []);

            $impactGelijk = $log->ai_impact === $log->agent_impact ? '✓' : '✗';
            $labelsGelijk = $log->labelsCorrect() ? '✓' : '✗';

            return implode("\n", [
                "Ticket: \"{$log->ticket_subject}\"",
                "Beschrijving: \"{$log->ticket_description_snippet}\"",
                "AI impact: {$log->ai_impact} {$impactGelijk} → Agent: {$log->agent_impact}",
                "AI labels: [{$aiLabels}] {$labelsGelijk} → Agent: [{$agentLabels}]",
                "Type correctie: {$log->correction_type}",
                "Skill versie gebruikt: {$log->ai_skill_version}",
            ]);
        })->join("\n\n---\n\n");
    }

    private function buildUpdatePrompt(string $currentSkill, string $corrections, int $count): string
    {
        return <<<PROMPT
Je bent een AI trainer voor een helpdesk labeling systeem.

Jouw taak: analyseer de correcties die agenten maakten op jouw eerdere labels,
en schrijf een bijgewerkte versie van het skill bestand zodat je volgende keer
beter presteert.

## Huidig skill bestand

{$currentSkill}

## Nieuwe correcties van agenten ({$count} stuks)

{$corrections}

## Instructies voor de update

Analyseer de correcties en doe het volgende:

1. Identificeer patronen — welke soort tickets label jij consequent verkeerd?
2. Voeg concrete nieuwe regels toe aan de sectie "Geleerde regels uit correcties"
3. Voeg de meest leerzame correcties toe als voorbeelden in "Correctie voorbeelden"
4. Als een correctie bevestigt dat je het goed had (✓), hoef je niets aan te passen
5. Verhoog de versie (v1.0 → v1.1, v1.1 → v1.2, etc.)
6. Update "Gebaseerd op: X correcties" met het nieuwe totaal
7. Update de datum naar vandaag

Regels voor goede instructies:
- Wees specifiek en concreet, geen vage regels
- Gebruik herkenbare patronen uit de echte correcties
- Maximaal 3 nieuwe regels per update om het bestand leesbaar te houden
- Maximaal 2 nieuwe voorbeelden per update

Geef het VOLLEDIGE bijgewerkte skill bestand terug in Markdown.
Geen uitleg erbuiten, geen code blocks, alleen het Markdown bestand zelf.
PROMPT;
    }

    private function makeBackup(string $skillPath, string $content): void
    {
        if (! $content) {
            return;
        }

        $backupDir = storage_path('ai-skill/backups');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        file_put_contents(
            $backupDir . '/skill-' . now()->format('Y-m-d-His') . '.md',
            $content
        );
    }

    private function defaultSkill(): string
    {
        $today = now()->format('Y-m-d');

        return <<<MD
# AI Labeling Skill
**Versie:** v1.0
**Aangemaakt:** {$today}
**Gebaseerd op:** 0 correcties

---

## Onze definities

### Labels
- **bug** — reproduceerbaar defect, iets werkt anders dan verwacht of beloofd
- **feature request** — klant vraagt om nieuwe of uitgebreide functionaliteit
- **onderzoek** — probleem is onduidelijk, meer informatie nodig voor classificatie
- **eigenlijk niet voor ons** — probleem ligt buiten onze verantwoordelijkheid of scope

### Impact
- **low** — klant kan verder werken, kleine hinder, geen tijdsdruk
- **medium** — klant heeft hinder maar heeft een workaround beschikbaar
- **high** — klant ligt stil, productie geblokkeerd, geen workaround mogelijk

---

## Geleerde regels uit correcties

*Nog geen regels geleerd — systeem is gestart.*

---

## Correctie voorbeelden

*Nog geen voorbeelden beschikbaar.*

---

## Moeilijke gevallen

*Wordt aangevuld naarmate het systeem leert.*
MD;
    }
}