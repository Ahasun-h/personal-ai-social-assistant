<?php

return [
    'platforms' => [
        'x_twitter' => [
            'name' => 'X (Twitter)',
            'max_characters' => 280,
            'url_characters' => 23,
            'max_photos' => 4,
            'max_videos' => 1,
            'max_video_size_mb' => 512,
            'costs' => [
                'post_text_only' => (float) env('METERED_X_POST_USD', 0.015),
                'post_with_link' => (float) env('METERED_X_POST_LINK_USD', 0.20),
                'read_request' => (float) env('METERED_X_READ_USD', 0.005),
            ],
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'max_characters' => 3000,
            'url_characters' => 0, // In LinkedIn, URLs take actual length unless card
            'max_photos' => 9,
            'max_videos' => 1,
            'max_video_size_mb' => 200,
            'costs' => [
                'post_text_only' => 0.0,
                'post_with_link' => 0.0,
                'read_request' => 0.0,
            ],
        ],
    ],

    'budget' => [
        'daily_cap_usd' => (float) env('DAILY_BUDGET_CAP_USD', env('BUDGET_DAILY_CAP_USD', 10.00)),
        'monthly_cap_usd' => (float) env('MONTHLY_BUDGET_CAP_USD', env('BUDGET_MONTHLY_CAP_USD', 150.00)),
        'warning_threshold' => 0.80, // Warn at 80% usage
    ],

    'tokens' => [
        'linkedin_validity_days' => 60,
        'expiry_warning_days' => 7,
    ],
];
