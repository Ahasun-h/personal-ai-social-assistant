<?php

return [
    'twitter' => [
        'client_id' => env('X_CLIENT_ID'),
        'client_secret' => env('X_CLIENT_SECRET'),
        'redirect_uri' => env('X_REDIRECT_URI', env('APP_URL') . '/api/v1/accounts/oauth/x_twitter/callback'),
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect_uri' => env('LINKEDIN_REDIRECT_URI', env('APP_URL') . '/api/v1/accounts/oauth/linkedin/callback'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'replicate' => [
        'api_token' => env('REPLICATE_API_TOKEN'),
    ],

    'fal' => [
        'key' => env('FAL_KEY'),
    ],
];
