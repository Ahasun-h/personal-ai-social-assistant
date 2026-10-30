<?php

use Illuminate\Support\Str;

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'social_assistant_horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis:publish-critical' => 30,
        'redis:ai-generation' => 120,
        'redis:media-transcode' => 300,
        'redis:trend-sync' => 60,
        'redis:analytics-sync' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,
    'memory_limit' => 256,

    'defaults' => [
        'supervisor-publisher' => [
            'connection' => 'redis',
            'queue' => ['publish-critical'],
            'balance' => 'simple',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 5,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-ai' => [
            'connection' => 'redis',
            'queue' => ['ai-generation'],
            'balance' => 'auto',
            'maxProcesses' => 5,
            'memory' => 512,
            'tries' => 2,
            'timeout' => 180,
        ],
        'supervisor-background' => [
            'connection' => 'redis',
            'queue' => ['media-transcode', 'trend-sync', 'analytics-sync', 'default'],
            'balance' => 'auto',
            'maxProcesses' => 5,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-publisher' => [
                'maxProcesses' => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-ai' => [
                'maxProcesses' => 5,
            ],
            'supervisor-background' => [
                'maxProcesses' => 5,
            ],
        ],
        'local' => [
            'supervisor-publisher' => [
                'maxProcesses' => 3,
            ],
            'supervisor-ai' => [
                'maxProcesses' => 2,
            ],
            'supervisor-background' => [
                'maxProcesses' => 2,
            ],
        ],
    ],
];
