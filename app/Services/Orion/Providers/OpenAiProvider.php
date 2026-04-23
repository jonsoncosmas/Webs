<?php

namespace App\Services\Orion\Providers;

use App\Services\Orion\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AiProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model = 'gpt-4o-mini',
    ) {}

    public function name(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function complete(string $prompt, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('OpenAI provider is not configured (OPENAI_API_KEY missing).');
        }

        $messages = [];
        if (! empty($options['system'])) {
            $messages[] = ['role' => 'system', 'content' => $options['system']];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $response = Http::timeout(60)
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? 1024,
                'temperature' => $options['temperature'] ?? 0.7,
            ])
            ->throw()
            ->json();

        return [
            'content' => $response['choices'][0]['message']['content'] ?? '',
            'model' => $response['model'] ?? $this->model,
            'input_tokens' => $response['usage']['prompt_tokens'] ?? null,
            'output_tokens' => $response['usage']['completion_tokens'] ?? null,
        ];
    }
}
