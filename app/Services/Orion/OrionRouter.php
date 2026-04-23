<?php

namespace App\Services\Orion;

use App\Models\AiRequest;
use App\Services\Orion\Contracts\AiProvider;
use Illuminate\Support\Facades\Log;

/**
 * ORION AI Router.
 *
 * Users never choose a model; ORION picks one based on task type:
 *   - report        -> Claude    (fallback: OpenAI -> Gemini -> DeepSeek)
 *   - explanation   -> OpenAI    (fallback: Claude -> Gemini -> DeepSeek)
 *   - data_analysis -> Gemini    (fallback: OpenAI -> Claude -> DeepSeek)
 *   - generic       -> Claude    (fallback: OpenAI -> Gemini -> DeepSeek)
 *
 * Users only ever see the brand name "ORION AI".
 */
class OrionRouter
{
    /** @var array<string, AiProvider> */
    private array $providers;

    /**
     * @param  array<string, AiProvider>  $providers  keyed by provider name
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @param  array{system?:string,max_tokens?:int,temperature?:float,user_id?:int,school_id?:int}  $options
     * @return array{content:string,provider:string,model:string,request_id:?int}
     */
    public function route(string $prompt, string $taskType = TaskType::GENERIC, array $options = []): array
    {
        $chain = $this->chainFor($taskType);
        $lastError = null;

        foreach ($chain as $providerName) {
            $provider = $this->providers[$providerName] ?? null;
            if (! $provider || ! $provider->isConfigured()) {
                continue;
            }

            $started = microtime(true);
            $log = AiRequest::create([
                'user_id' => $options['user_id'] ?? null,
                'school_id' => $options['school_id'] ?? null,
                'task_type' => $taskType,
                'provider' => $provider->name(),
                'prompt_hash' => hash('sha256', $prompt),
                'status' => 'pending',
            ]);

            try {
                $result = $provider->complete($prompt, $options);
                $log->update([
                    'model' => $result['model'] ?? null,
                    'input_tokens' => $result['input_tokens'] ?? null,
                    'output_tokens' => $result['output_tokens'] ?? null,
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                    'status' => 'success',
                ]);

                return [
                    'content' => $result['content'],
                    'provider' => $provider->name(),
                    'model' => $result['model'],
                    'request_id' => $log->id,
                ];
            } catch (\Throwable $e) {
                $lastError = $e;
                $log->update([
                    'status' => 'failed',
                    'error' => substr($e->getMessage(), 0, 500),
                    'latency_ms' => (int) ((microtime(true) - $started) * 1000),
                ]);
                Log::warning('ORION provider failed, falling back.', [
                    'provider' => $provider->name(),
                    'task_type' => $taskType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException(
            'ORION AI: no provider could handle the request.',
            previous: $lastError,
        );
    }

    /**
     * The primary provider ORION would have selected, purely for diagnostics.
     * Intentionally NOT exposed to non-admin users.
     */
    public function primaryProviderFor(string $taskType): string
    {
        return $this->chainFor($taskType)[0];
    }

    /**
     * @return array<int, string>
     */
    private function chainFor(string $taskType): array
    {
        return match ($taskType) {
            TaskType::REPORT => ['claude', 'openai', 'gemini', 'deepseek'],
            TaskType::EXPLANATION => ['openai', 'claude', 'gemini', 'deepseek'],
            TaskType::DATA_ANALYSIS => ['gemini', 'openai', 'claude', 'deepseek'],
            default => ['claude', 'openai', 'gemini', 'deepseek'],
        };
    }
}
