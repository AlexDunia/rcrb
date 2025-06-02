<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Settings
    |--------------------------------------------------------------------------
    |
    | This file contains various settings for the authentication system.
    |
    */

    'rate_limiting' => [
        'max_attempts' => env('AUTH_MAX_ATTEMPTS', 5),
        'decay_minutes' => env('AUTH_DECAY_MINUTES', 5),
    ],

    'token' => [
        'expiration_days' => env('TOKEN_EXPIRATION_DAYS', 1),
        'refresh_ttl' => env('TOKEN_REFRESH_TTL', 20160), // 14 days in minutes
    ],

    'cookie' => [
        'name' => env('AUTH_COOKIE_NAME', 'auth_token'),
        'same_site' => env('AUTH_COOKIE_SAME_SITE', 'Strict'),
        'secure' => env('AUTH_COOKIE_SECURE', true),
        'http_only' => env('AUTH_COOKIE_HTTP_ONLY', true),
    ],

    'security' => [
        'password_history' => env('PASSWORD_HISTORY_COUNT', 3),
        'require_2fa' => env('REQUIRE_2FA', false),
    ],
];
