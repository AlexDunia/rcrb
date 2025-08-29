<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'api/auth/init', 'api/auth/google/*', 'api/auth/yahoo/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
