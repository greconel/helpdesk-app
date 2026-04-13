<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeLogController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $request->validate([
            'started_at'       => 'nullable|date',
            'stopped_at'       => 'nullable|date|after:started_at',
            'hours'            => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        // Timer: bereken duur in seconden uit start- en stoptijd
        if ($request->started_at && $request->stopped_at) {
            $durationSeconds = (int) Carbon::parse($request->started_at)
                ->diffInSeconds(Carbon::parse($request->stopped_at));
            $duration = max(1, (int) round($durationSeconds / 60));
        } else {
            // Manuele invoer: uren + minuten samenvoegen
            $duration = (($request->hours ?? 0) * 60) + ($request->duration_minutes ?? 0);
        }

        if ($duration < 1) {
            return back()->with('error', 'Tijdsduur moet minimaal 1 minuut zijn.');
        }

        TimeLog::create([
            'ticket_id'        => $ticket->id,
            'user_id'          => auth()->id(),
            'started_at'       => $request->started_at ?: null,
            'stopped_at'       => $request->stopped_at ?: null,
            'duration_minutes' => $duration,
        ]);

        return back()->with('success', 'Tijd succesvol gelogd.');
    }
}