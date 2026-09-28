<?php

return [
    'default' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'api_key' => env('AI_API_KEY', env('OPENAI_API_KEY')),
            'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
            'model' => env('AI_MODEL', 'gpt-6-astra'),
            'max_tokens' => (int) env('AI_MAX_TOKENS', 1200),
            'temperature' => env('AI_TEMPERATURE'),
            'timeout' => (int) env('AI_TIMEOUT', 30),
            'retries' => (int) env('AI_RETRIES', 1),
            'input_cost_per_million_tokens' => (float) env('AI_INPUT_COST_PER_MILLION_TOKENS', 0),
            'output_cost_per_million_tokens' => (float) env('AI_OUTPUT_COST_PER_MILLION_TOKENS', 0),
        ],

        'fake' => [
            'model' => env('AI_MODEL', 'fake-model'),
            'response' => env('AI_FAKE_RESPONSE', '{}'),
        ],
    ],
];
