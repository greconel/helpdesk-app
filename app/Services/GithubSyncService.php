<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubSyncService
{
    private string $token;
    private string $repo;
    private string $docsPath;

    public function __construct()
    {
        $this->token    = config('services.github.token');
        $this->repo     = config('services.github.repo');
        $this->docsPath = config('services.github.docs_path', 'docs/');
    }

    public function sync(): int
    {
        $files = $this->getMarkdownFiles();

        if (empty($files)) {
            Log::warning('GitHubSync: geen bestanden gevonden in ' . $this->docsPath);
            return 0;
        }

        $count = 0;

        foreach ($files as $file) {
            $content = $this->fetchFileContent($file['download_url']);

            if (!$content) {
                continue;
            }

            KnowledgeChunk::updateOrCreate(
                [
                    'source_path' => $file['path'],
                ],
                [
                    'title'      => $this->extractTitle($content),
                    'content'    => $content,
                    'synced_at'  => now(),
                ]
            );

            $count++;
        }

        return $count;
    }

    private function getMarkdownFiles(): array
    {
        $response = Http::withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
            ])
            ->get("https://api.github.com/repos/{$this->repo}/contents/{$this->docsPath}");

        if (!$response->successful()) {
            Log::error('GitHubSync: ophalen bestanden mislukt', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return [];
        }

        return collect($response->json())
            ->filter(fn($file) => str_ends_with($file['name'], '.md'))
            ->values()
            ->toArray();
    }

    private function fetchFileContent(string $downloadUrl): ?string
    {
        $response = Http::withToken($this->token)
            ->get($downloadUrl);

        if (!$response->successful()) {
            Log::error('GitHubSync: ophalen bestandsinhoud mislukt', [
                'url' => $downloadUrl,
            ]);
            return null;
        }

        return $response->body();
    }

    private function extractTitle(string $content): string
    {
        preg_match('/^#\s+(.+)/m', $content, $matches);
        return $matches[1] ?? 'Zonder titel';
    }
}