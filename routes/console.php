<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verwerk inkomende mails elke minuut
Schedule::command('mail:process')->everyMinute();

// Update het AI skill bestand elke nacht om 02:00
// Alleen als er minimaal 5 nieuwe correcties zijn
Schedule::command('ai:update-skill')->dailyAt('02:00');

// Synchroniseer Motion taakstatussen naar tickets (elke 5 minuten)
Schedule::command('motion:sync')->everyFiveMinutes();

// Herschrijf het AI skill bestand als er 10+ verwerkte correcties zijn (wekelijks op zondag)
Schedule::command('ai:rewrite-skill')->weekly()->sundays()->at('03:00');