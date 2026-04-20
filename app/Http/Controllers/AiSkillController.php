<?php

namespace App\Http\Controllers;

use App\Models\AiCorrectionLog;
use Illuminate\Http\Request;

class AiSkillController extends Controller
{
    private string $skillPath;

    public function __construct()
    {
        $this->skillPath = storage_path('ai-skill/labeling-skill.md');
    }

    public function index()
    {
        $skillContent = file_exists($this->skillPath)
            ? file_get_contents($this->skillPath)
            : '';

        $corrections = AiCorrectionLog::with(['ticket', 'agent'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('ai-skill.index', compact('skillContent', 'corrections'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'skill_content' => 'required|string',
        ]);

        // Backup maken
        $backupDir = storage_path('ai-skill/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if (file_exists($this->skillPath)) {
            $backupPath = $backupDir . '/skill-' . now()->format('Y-m-d-His') . '-manual.md';
            copy($this->skillPath, $backupPath);
        }

        // Versie ophogen
        $content = $validated['skill_content'];
        $content = preg_replace_callback(
            '/\*\*Versie:\*\*\s*v(\d+)\.(\d+)/m',
            function ($matches) {
                return '**Versie:** v' . $matches[1] . '.' . ($matches[2] + 1);
            },
            $content
        );

        // Datum bijwerken
        $content = preg_replace(
            '/\*\*Laatst bijgewerkt:\*\*.*$/m',
            '**Laatst bijgewerkt:** ' . now()->format('Y-m-d'),
            $content
        );

        if (!is_dir(dirname($this->skillPath))) {
            mkdir(dirname($this->skillPath), 0755, true);
        }

        file_put_contents($this->skillPath, $content);

        return back()->with('success', 'Skill bestand opgeslagen en versie bijgewerkt. Backup bewaard.');
    }
}