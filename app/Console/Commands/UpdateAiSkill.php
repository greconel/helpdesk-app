<?php

namespace App\Console\Commands;

use App\Models\AiCorrectionLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateAiSkill extends Command
{
    protected $signature   = 'ai:update-skill {--dry-run : Toon het nieuwe skill bestand zonder op te slaan} {--min=5 : Minimum aantal correcties voor update}';
    protected $description = 'Update het AI skill bestand op basis van agentcorrecties';

    public function handle(): int
    {
        $minimum = (int) $this->option('min');

        $this->info('📊 Onverwerkte correcties ophalen...');

        $corrections = AiCorrectionLog::where('processed', false)
            ->where('ignore_in_training', false)
            ->with(['ticket', 'agent'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($corrections->isEmpty()) {
            $this->info('Geen nieuwe correcties gevonden. Skill blijft ongewijzigd.');
            return self::SUCCESS;
        }

        $this->info("{$corrections->count()} correctie(s) gevonden.");

        if ($corrections->count() < $minimum) {
            $this->warn("Minder dan {$minimum} correcties — nog niet genoeg om van te leren.");
            $this->warn("Gebruik --min=1 om toch te updaten, of wacht op meer correcties.");
            return self::SUCCESS;
        }

        // Huidig skill bestand inladen
        $skillPath    = storage_path('ai-skill/labeling-skill.md');
        $currentSkill = file_exists($skillPath)
            ? file_get_contents($skillPath)
            : $this->defaultSkill();

        // Correcties omzetten naar leesbare tekst voor Claude
        $correctionText = $this->formatCorrections($corrections);

        $this->info('🤖 Claude analyseert correcties en schrijft nieuwe regels...');

        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 2000,
            'messages'   => [[
                'role'    => 'user',
                'content' => $this->buildUpdatePrompt($currentSkill, $correctionText, $corrections->count()),
            ]],
        ]);

        if (!$response->successful()) {
            $this->error('API call mislukt: ' . $response->body());
            Log::error('UpdateAiSkill: API call mislukt', ['body' => $response->body()]);
            return self::FAILURE;
        }

        $newSkill = $response->json('content.0.text');

        // Verwijder eventuele markdown code block tags die Claude toevoegt
        $newSkill = preg_replace('/^```markdown\s*/i', '', $newSkill);
        $newSkill = preg_replace('/^```\s*/im', '', $newSkill);
        $newSkill = trim($newSkill);

        // Dry run: toon resultaat zonder op te slaan
        if ($this->option('dry-run')) {
            $this->line('');
            $this->line('--- NIEUW SKILL BESTAND (dry-run, niet opgeslagen) ---');
            $this->line('');
            $this->line($newSkill);
            $this->line('');
            $this->line('--- EINDE DRY RUN ---');
            return self::SUCCESS;
        }

        // Backup maken van huidige versie
        $this->makeBackup($skillPath, $currentSkill);

        // Nieuw skill bestand opslaan
        if (!is_dir(dirname($skillPath))) {
            mkdir(dirname($skillPath), 0755, true);
        }
        file_put_contents($skillPath, $newSkill);

        // Correcties markeren als verwerkt
        AiCorrectionLog::whereIn('id', $corrections->pluck('id'))->update([
            'processed' => true,
        ]);

        $this->info("✅ Skill bestand bijgewerkt op basis van {$corrections->count()} correctie(s).");
        $this->info('💾 Backup bewaard in storage/ai-skill/backups/');

        Log::info('AI skill bijgewerkt', [
            'corrections_verwerkt' => $corrections->count(),
        ]);

        return self::SUCCESS;
    }

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
        if (!$content) {
            return;
        }

        $backupDir = storage_path('ai-skill/backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupPath = $backupDir . '/skill-' . now()->format('Y-m-d-His') . '.md';
        file_put_contents($backupPath, $content);
    }

    private function defaultSkill(): string
    {
        return <<<MD
# AI Labeling Skill
**Versie:** v1.0
**Aangemaakt:** {$this->today()}
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

    private function today(): string
    {
        return now()->format('Y-m-d');
    }
}