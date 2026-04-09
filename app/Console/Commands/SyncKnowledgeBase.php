<?php

namespace App\Console\Commands;

use App\Services\GitHubSyncService;
use Illuminate\Console\Command;

class SyncKnowledgeBase extends Command
{
    protected $signature   = 'knowledge:sync';
    protected $description = 'Synchroniseer de kennisbank vanuit GitHub';

    public function handle(GitHubSyncService $github): int
    {
        $this->info('Kennisbank synchroniseren...');

        $count = $github->sync();

        if ($count === 0) {
            $this->warn('Geen documenten gesynchroniseerd. Controleer je GitHub instellingen.');
            return self::FAILURE;
        }

        $this->info("Klaar. {$count} document(en) gesynchroniseerd.");

        return self::SUCCESS;
    }
}