<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Verwerk inkomende mails elke minuut
Schedule::command('mail:process')->everyMinute()->onOneServer();

// Synchroniseer Motion taakstatussen naar tickets (elke 5 minuten)
// Kan later vervangen worden door Motion webhooks als die beschikbaar komen
Schedule::command('motion:sync')->everyFiveMinutes()->onOneServer();

