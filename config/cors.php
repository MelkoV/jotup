<?php

declare(strict_types=1);

use Jotup\Env;

$allowedOrigins = explode(',', (string)Env::get('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173'))
        |> (fn($x) => array_map(static fn(string $origin): string => trim($origin), $x))
        |> array_filter(...)
        |> array_values(...);

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
