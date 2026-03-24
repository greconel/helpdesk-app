<?php
// app/Console/Commands/TestGraphConnection.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGraphConnection extends Command
{
    protected $signature   = 'graph:test';
    protected $description = 'Test de Microsoft Graph API verbinding';

    // app/Console/Commands/TestGraphConnection.php

    public function handle(): int
    {
        $this->info('Stap 1: Access token ophalen...');

        $tokenResponse = Http::asForm()->post(
            'https://login.microsoftonline.com/' . config('services.microsoft.tenant_id') . '/oauth2/v2.0/token',
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$tokenResponse->successful()) {
            $this->error('✗ Token ophalen mislukt');
            $this->line($tokenResponse->body());
            return self::FAILURE;
        }

        $this->info('✓ Access token ontvangen');
        $token = $tokenResponse->json('access_token');

        $this->info('Stap 2: Inbox uitlezen van ' . config('services.microsoft.mailbox') . '...');

        $inboxResponse = Http::withToken($token)
            ->get('https://graph.microsoft.com/v1.0/users/' . config('services.microsoft.mailbox') . '/mailFolders/inbox/messages?$top=10&$select=subject,from,receivedDateTime,isRead&$orderby=receivedDateTime desc');

        if (!$inboxResponse->successful()) {
            $this->error('✗ Inbox uitlezen mislukt');
            $this->line($inboxResponse->body());
            return self::FAILURE;
        }

        $messages = $inboxResponse->json('value', []);
        $this->info('✓ ' . count($messages) . ' berichten gevonden');
        $this->newLine();

        $headers = ['Status', 'Datum', 'Van', 'Onderwerp'];
        $rows    = [];

        foreach ($messages as $msg) {
            $rows[] = [
                $msg['isRead'] ? 'Gelezen' : 'ONGELEZEN',
                \Carbon\Carbon::parse($msg['receivedDateTime'])->setTimezone('Europe/Brussels')->format('d-m-Y H:i'),
                data_get($msg, 'from.emailAddress.address', '?'),
                mb_substr($msg['subject'] ?? '(geen onderwerp)', 0, 50),
            ];
        }

        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}