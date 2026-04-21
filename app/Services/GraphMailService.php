<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphMailService
{
    private string $baseUrl = 'https://graph.microsoft.com/v1.0';
    private ?string $token  = null;

    public function getToken(): string
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
        $mailbox  = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/mailFolders/inbox/messages", [
                '$top'     => $top,
                '$filter'  => 'isRead eq false',
                '$select'  => 'id,subject,from,body,receivedDateTime,internetMessageId,conversationId,isRead,hasAttachments',
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
        $mailbox  = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}");

        return $response->successful() ? $response->json() : null;
    }

    public function getMessageHeaders(string $messageId): array
    {
        $mailbox  = config('services.microsoft.mailbox');
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

    public function getAttachments(string $messageId): array
    {
        $mailbox  = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}/attachments");

        if (!$response->successful()) {
            Log::error('Graph getAttachments mislukt', ['body' => $response->body()]);
            return [];
        }

        return $response->json('value', []);
    }

    /**
     * Haal de ruwe bytes op van een specifieke bijlage.
     * Nodig wanneer contentBytes niet in de standaard response zit (grote bestanden).
     */
    public function getAttachmentContent(string $messageId, string $attachmentId): ?string
    {
        $mailbox  = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}/attachments/{$attachmentId}/\$value");

        if (!$response->successful()) {
            Log::error('Graph getAttachmentContent mislukt', [
                'message_id'    => $messageId,
                'attachment_id' => $attachmentId,
                'body'          => $response->body(),
            ]);
            return null;
        }

        return $response->body();
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
        $mailbox  = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->post("{$this->baseUrl}/users/{$mailbox}/sendMail", $payload);

        if (!$response->successful()) {
            Log::error('Graph sendMail mislukt', ['body' => $response->body()]);
            return false;
        }
        return true;
    }

    public function markAsUnread(string $messageId): void
    {
        $mailbox = config('services.microsoft.mailbox');
        Http::withToken($this->getToken())
            ->patch("{$this->baseUrl}/users/{$mailbox}/messages/{$messageId}", [
                'isRead' => false,
            ]);
    }

    public function getLatestMessages(int $top = 2): array
    {
        $mailbox  = config('services.microsoft.mailbox');
        $response = Http::withToken($this->getToken())
            ->get("{$this->baseUrl}/users/{$mailbox}/mailFolders/inbox/messages", [
                '$top'     => $top,
                '$select'  => 'id,subject,receivedDateTime',
                '$orderby' => 'receivedDateTime desc',
            ]);

        return $response->successful() ? $response->json('value', []) : [];
    }
}