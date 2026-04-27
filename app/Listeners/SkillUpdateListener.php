<?php

namespace App\Listeners;

use App\Events\AiCorrectionLogCreated;
use App\Jobs\UpdateAiSkillJob;
use App\Models\AiCorrectionLog;
use Illuminate\Support\Facades\Log;

class SkillUpdateListener
{
    /**
     * Minimaal aantal onverwerkte correcties voordat de job automatisch start.
     */
    private const THRESHOLD = 10;

    public function handle(AiCorrectionLogCreated $event): void
    {
        $pending = AiCorrectionLog::where('processed', false)
            ->where('ignore_in_training', false)
            ->count();

        if ($pending < self::THRESHOLD) {
            return;
        }

        // Voorkom dubbele dispatches als de job al in de queue staat
        // door te checken of er al een UpdateAiSkillJob pending is.
        // Laravel heeft geen ingebouwde check hiervoor, dus we gebruiken
        // een simpele cache-lock van 10 minuten als debounce.
        $lockKey = 'update_ai_skill_dispatched';

        if (cache()->has($lockKey)) {
            return;
        }

        cache()->put($lockKey, true, now()->addMinutes(10));

        UpdateAiSkillJob::dispatch();

        Log::info("SkillUpdateListener: job gedispatcht na {$pending} correcties.");
    }
}