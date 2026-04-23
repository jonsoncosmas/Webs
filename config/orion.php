<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ORION AI Router Config
    |--------------------------------------------------------------------------
    |
    | Users ALWAYS interact with the system as "ORION AI". Real provider
    | identities are only visible to System Admin. Task routing is hard-coded
    | in \App\Services\Orion\OrionRouter so that users cannot manually choose.
    |
    */

    'brand' => env('ORION_BRAND', 'ORION AI'),

    'providers' => [
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        ],
    ],
];
