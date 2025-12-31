<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubApiClient
{
    private string $baseUrl = 'https://api.github.com';

    public function __construct(
        private string $repository,
        private string $branch = 'main',
        private ?string $token = null
    ) {}

    public function testConnection(): bool
    {
        try {
            $response = $this->request('GET', "/repos/{$this->repository}");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLatestCommit(): ?array
    {
        $response = $this->request('GET', "/repos/{$this->repository}/commits/{$this->branch}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return [
            'sha' => $data['sha'],
            'message' => $data['commit']['message'],
            'author' => $data['commit']['author']['name'],
            'date' => $data['commit']['author']['date'],
        ];
    }

    public function getCommitDetails(string $commitHash): ?array
    {
        $response = $this->request('GET', "/repos/{$this->repository}/commits/{$commitHash}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    public function getChangedFiles(string $fromCommit, string $toCommit): array
    {
        $response = $this->request(
            'GET',
            "/repos/{$this->repository}/compare/{$fromCommit}...{$toCommit}"
        );

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return collect($data['files'] ?? [])
            ->filter(fn ($file) => str_starts_with($file['filename'], 'docs/'))
            ->map(fn ($file) => [
                'filename' => $file['filename'],
                'status' => $file['status'], // added, modified, removed
                'additions' => $file['additions'],
                'deletions' => $file['deletions'],
            ])
            ->toArray();
    }

    public function getFileContent(string $path): ?string
    {
        $response = $this->request(
            'GET',
            "/repos/{$this->repository}/contents/{$path}",
            ['ref' => $this->branch]
        );

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        // GitHub returns base64 encoded content
        return base64_decode($data['content']);
    }

    public function getDirectoryTree(string $path = 'docs'): array
    {
        $treeSha = $this->getRootTreeShaForBranch();

        if (! $treeSha) {
            return [];
        }

        $response = $this->request(
            'GET',
            "/repos/{$this->repository}/git/trees/{$treeSha}",
            ['recursive' => 1]
        );

        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return collect($data['tree'] ?? [])
            ->filter(fn ($item) => str_starts_with($item['path'], $path))
            ->toArray();
    }

    private function getRootTreeShaForBranch(): ?string
    {
        $response = $this->request('GET', "/repos/{$this->repository}/commits/{$this->branch}");

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return $data['commit']['tree']['sha'] ?? null;
    }

    public function downloadFile(string $path, string $destination): bool
    {
        $content = $this->getFileContent($path);

        if ($content === null) {
            return false;
        }

        return file_put_contents($destination, $content) !== false;
    }

    private function request(string $method, string $endpoint, array $params = []): Response|PromiseInterface
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        if ($this->token) {
            $headers['Authorization'] = "Bearer {$this->token}";
        }

        $request = Http::withHeaders($headers)
            ->timeout(30);

        $url = $this->baseUrl.$endpoint;

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $params),
            'POST' => $request->post($url, $params),
            'PUT' => $request->put($url, $params),
            'DELETE' => $request->delete($url, $params),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }
}
