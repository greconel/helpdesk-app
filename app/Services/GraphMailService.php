<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphMailService
{
    private string $baseUrl = 'https://graph.microsoft.com/v1.0';
    private ?string $token = null;

    private function getToken(): string
    {
        if ($this->token) return $this->token;

        $response = Http::asForm()->post(
            'https://login.microsoftonline.com/' . config('services.microsoft.tenant_id') . '/oauth2/v2.0/token',
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Graph token ophalen mislukt: ' . $response->body());
        }

        $this->token = $response->json('access_token');
        return $this->token;
    }

    public function getUnreadMessages(int $top = 25): array
    {
        $mailbox = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/mailFolders/inbox/messages", [
                '$top'     => $top,
                '$filter'  => 'isRead eq false',
                '$select'  => 'id,subject,from,body,receivedDateTime,internetMessageId,conversationId,isRead',
                '$orderby' => 'receivedDateTime asc',
            ]);

        if (!$response->successful()) {
            Log::error('Graph getUnreadMessages mislukt', ['body' => $response->body()]);
            return [];
        }

        return $response->json('value', []);
    }

    public function getMessage(string $messageId): ?array
    {
        $mailbox = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}");

        return $response->successful() ? $response->json() : null;
    }

    public function getMessageHeaders(string $messageId): array
    {
        $mailbox = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}", [
                '$select' => 'internetMessageHeaders,internetMessageId',
            ]);

        if (!$response->successful()) return [];

        $headers = [];
        foreach ($response->json('internetMessageHeaders', []) as $h) {
            $headers[strtolower($h['name'])] = $h['value'];
        }
        return $headers;
    }

    public function markAsRead(string $messageId): void
    {
        $mailbox = config('services.microsoft.mailbox');
        Http::withToken($this->getToken())
            ->patch("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}", [
                'isRead' => true,
            ]);
    }

    public function sendMail(array $payload): bool
    {
        $mailbox = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->post("{$this->baseUrl}/users/{$mailbox}/sendMail", $payload);

        if (!$response->successful()) {
            Log::error('Graph sendMail mislukt', ['body' => $response->body()]);
            return false;
        }
        return true;
    }
}