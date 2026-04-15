<?php

namespace App\Http\Controllers;

use App\Models\AiCorrectionLog;
use Illuminate\Http\Request;

class AiCorrectionController extends Controller
{
    public function toggleIgnore(Request $request, AiCorrectionLog $log)
    {
        $validated = $request->validate([
            'ignore_in_training' => 'required|boolean',
            'ignore_reason'      => 'nullable|string|max:500',
        ]);

        $log->update([
            'ignore_in_training' => $validated['ignore_in_training'],
            'ignore_reason'      => $validated['ignore_in_training']
                ? ($validated['ignore_reason'] ?? null)
                : null,
        ]);

        $label = $validated['ignore_in_training']
            ? 'gemarkeerd als uitzondering'
            : 'uitzondering opgeheven';

        return back()->with('success', "Correctie {$label}.");
    }
}