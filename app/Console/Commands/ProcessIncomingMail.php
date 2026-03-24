<?php
namespace App\Console\Commands;

use App\Services\MailToTicketService;
use Illuminate\Console\Command;

class ProcessIncomingMail extends Command
{
    protected $signature   = 'mail:process';
    protected $description = 'Verwerk ongelezen e-mails uit de shared mailbox';

    public function handle(MailToTicketService $service): int
    {
        $this->info('E-mails ophalen...');
        $before = \App\Models\Ticket::count();
        $service->processUnreadMails();
        $after = \App\Models\Ticket::count();

        $this->info("Klaar. " . ($after - $before) . " nieuwe ticket(s) aangemaakt.");
        return self::SUCCESS;
    }
}