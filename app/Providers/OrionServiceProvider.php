<?php

namespace App\Providers;

use App\Services\Orion\OrionRouter;
use App\Services\Orion\Providers\ClaudeProvider;
use App\Services\Orion\Providers\DeepSeekProvider;
use App\Services\Orion\Providers\GeminiProvider;
use App\Services\Orion\Providers\OpenAiProvider;
use Illuminate\Support\ServiceProvider;

class OrionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OrionRouter::class, function ($app) {
            $cfg = $app['config']->get('orion.providers');

            return new OrionRouter([
                'claude' => new ClaudeProvider($cfg['claude']['api_key'] ?? null, $cfg['claude']['model'] ?? 'claude-sonnet-4-5'),
                'openai' => new OpenAiProvider($cfg['openai']['api_key'] ?? null, $cfg['openai']['model'] ?? 'gpt-4o-mini'),
                'gemini' => new GeminiProvider($cfg['gemini']['api_key'] ?? null, $cfg['gemini']['model'] ?? 'gemini-1.5-flash'),
                'deepseek' => new DeepSeekProvider($cfg['deepseek']['api_key'] ?? null, $cfg['deepseek']['model'] ?? 'deepseek-chat'),
            ]);
        });
    }
}
