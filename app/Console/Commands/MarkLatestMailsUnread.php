<?php

namespace App\Console\Commands;

use App\Services\GraphMailService;
use Illuminate\Console\Command;

class MarkLatestMailsUnread extends Command
{
    protected $signature   = 'mail:unread {count=1}';
    protected $description = 'Zet de laatste X mails terug op ongelezen';

    public function handle(GraphMailService $graph): int
    {
        $count    = (int) $this->argument('count');
        $messages = $graph->getLatestMessages($count);

        if (empty($messages)) {
            $this->info('Geen mails gevonden.');
            return self::SUCCESS;
        }

        foreach ($messages as $msg) {
            $graph->markAsUnread($msg['id']);
            $this->info("Op ongelezen gezet: {$msg['subject']}");
        }

        return self::SUCCESS;
    }
}