<?php

namespace App\Console\Commands;

use App\Services\MotionService;
use Illuminate\Console\Command;

class ListMotionTemplates extends Command
{
    protected $signature   = 'motion:templates';
    protected $description = 'Toon alle beschikbare Motion project templates';

// app/Console/Commands/ListMotionTemplates.php
public function handle(MotionService $motion): int
{
    $definitions = $motion->getProjectDefinitions();

    if (empty($definitions)) {
        $this->error('Geen project definitions gevonden of API-fout.');
        return self::FAILURE;
    }

    $this->table(
        ['ID', 'Naam', 'Stages'],
        collect($definitions)->map(fn($d) => [
            $d['id']   ?? '?',
            $d['name'] ?? '?',
            collect($d['stages'] ?? [])->pluck('name')->implode(', '),
        ])->toArray()
    );

    return self::SUCCESS;
}
}