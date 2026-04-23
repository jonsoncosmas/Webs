<?php

namespace App\Services\Orion\Providers;

use App\Services\Orion\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProvider
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model = 'gemini-1.5-flash',
    ) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function complete(string $prompt, array $options = []): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Gemini provider is not configured (GEMINI_API_KEY missing).');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $this->model,
            $this->apiKey,
        );

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
        ];
        if (! empty($options['system'])) {
            $payload['systemInstruction'] = ['parts' => [['text' => $options['system']]]];
        }

        $response = Http::timeout(60)->post($url, $payload)->throw()->json();

        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return [
            'content' => $text,
            'model' => $this->model,
            'input_tokens' => $response['usageMetadata']['promptTokenCount'] ?? null,
            'output_tokens' => $response['usageMetadata']['candidatesTokenCount'] ?? null,
        ];
    }
}
