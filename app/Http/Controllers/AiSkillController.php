<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateAiSkillJob;
use App\Models\AiCorrectionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AiSkillController extends Controller
{
    private string $skillPath;

    public function __construct()
    {
        $this->skillPath = storage_path('ai-skill/labeling-skill.md');
    }

    public function index(Request $request)
    {
        $skillContent = file_exists($this->skillPath)
            ? file_get_contents($this->skillPath)
            : '';

        $skillLastUpdatedAt = file_exists($this->skillPath)
            ? Carbon::createFromTimestamp(filemtime($this->skillPath))
                ->setTimezone(config('app.timezone', 'UTC'))
            : null;

        $corrections = AiCorrectionLog::with(['ticket', 'agent'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $pendingCount = AiCorrectionLog::where('processed', false)
            ->where('ignore_in_training', false)
            ->count();

        return view('ai-skill.index', compact(
            'skillContent',
            'skillLastUpdatedAt',
            'corrections',
            'pendingCount',
        ));
    }

    public function triggerUpdate()
    {
        $pending = AiCorrectionLog::where('processed', false)
            ->where('ignore_in_training', false)
            ->count();

        if ($pending === 0) {
            return back()->with('info', 'Geen onverwerkte correcties — skill is al up-to-date.');
        }

        UpdateAiSkillJob::dispatch();

        return back()->with('success', "Skill update gestart voor {$pending} correctie(s). Dit wordt op de achtergrond verwerkt.");
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'skill_content' => 'required|string',
        ]);

        $backupDir = storage_path('ai-skill/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if (file_exists($this->skillPath)) {
            $backupPath = $backupDir . '/skill-' . now()->format('Y-m-d-His') . '-manual.md';
            copy($this->skillPath, $backupPath);
        }

        $content = $validated['skill_content'];
        $content = preg_replace_callback(
            '/\*\*Versie:\*\*\s*v(\d+)\.(\d+)/m',
            fn ($m) => '**Versie:** v' . $m[1] . '.' . ($m[2] + 1),
            $content
        );
        $content = preg_replace(
            '/\*\*Laatst bijgewerkt:\*\*.*$/m',
            '**Laatst bijgewerkt:** ' . now()->format('Y-m-d'),
            $content
        );

        if (! is_dir(dirname($this->skillPath))) {
            mkdir(dirname($this->skillPath), 0755, true);
        }

        file_put_contents($this->skillPath, $content);

        return back()->with('success', 'Skill bestand opgeslagen en versie bijgewerkt. Backup bewaard.');
    }
}