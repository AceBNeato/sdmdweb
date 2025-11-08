<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DDOS Protection Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for DDOS protection middleware
    |
    */

    'enabled' => env('DDOS_PROTECTION_ENABLED', true),

    'rate_limits' => [
        'per_ip' => [
            'attempts' => env('DDOS_RATE_LIMIT_PER_IP', 100),
            'decay_minutes' => env('DDOS_RATE_LIMIT_DECAY_IP', 1),
        ],

        'per_route' => [
            'attempts' => env('DDOS_RATE_LIMIT_PER_ROUTE', 30),
            'decay_minutes' => env('DDOS_RATE_LIMIT_DECAY_ROUTE', 1),
        ],

        'global' => [
            'attempts' => env('DDOS_RATE_LIMIT_GLOBAL', 1000),
            'decay_minutes' => env('DDOS_RATE_LIMIT_DECAY_GLOBAL', 1),
        ],
    ],

    'suspicious_patterns' => [
        'user_agents' => [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'python-requests',
            'curl',
            'wget',
            'postman',
            'insomnia',
        ],

        'headers' => [
            'multiple_x_forwarded_for',
            'empty_accept',
            'suspicious_referer',
        ],
    ],

    'whitelist' => [
        'ips' => env('DDOS_WHITELIST_IPS', ''),
        'user_agents' => env('DDOS_WHITELIST_UA', ''),
    ],

    'blacklist' => [
        'ips' => env('DDOS_BLACKLIST_IPS', ''),
        'countries' => env('DDOS_BLACKLIST_COUNTRIES', ''),
    ],

    'logging' => [
        'enabled' => env('DDOS_LOGGING_ENABLED', true),
        'level' => env('DDOS_LOG_LEVEL', 'warning'),
        'detailed' => env('DDOS_LOG_DETAILED', false),
    ],

    'response' => [
        'blocked_message' => env('DDOS_BLOCKED_MESSAGE', 'Too many requests. Please try again later.'),
        'retry_after_header' => env('DDOS_RETRY_AFTER_HEADER', true),
    ],
];
