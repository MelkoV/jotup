<?php

declare(strict_types=1);

use Jotup\Env;

return [
    'key' => Env::get('JWT_KEY', 'dev-jwt-key'),
    'alg' => Env::get('JWT_ALG', 'HS256'),
    'cookie' => [
        'name' => Env::get('JWT_REFRESH_COOKIE_NAME', 'refresh_token'),
        'domain' => Env::get('JWT_REFRESH_COOKIE_DOMAIN'),
        'secure' => Env::getBool('JWT_REFRESH_COOKIE_SECURE', true),
        'same_site' => Env::get('JWT_REFRESH_SAME_SITE', 'none'),
    ],
];
