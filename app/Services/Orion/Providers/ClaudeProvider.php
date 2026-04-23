<?php

namespace App\Services\Orion\Providers;

use App\Services\Orion\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements AiProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model = 'claude-sonnet-4-5',
    ) {}

    public function name(): string
    {
        return 'claude';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function complete(string $prompt, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Claude provider is not configured (ANTHROPIC_API_KEY missing).');
        }

        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $options['max_tokens'] ?? 1024,
                'system' => $options['system'] ?? null,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ])
            ->throw()
            ->json();

        return [
            'content' => $response['content'][0]['text'] ?? '',
            'model' => $response['model'] ?? $this->model,
            'input_tokens' => $response['usage']['input_tokens'] ?? null,
            'output_tokens' => $response['usage']['output_tokens'] ?? null,
        ];
    }
}
