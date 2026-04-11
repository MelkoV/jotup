<?php

declare(strict_types=1);

use Jotup\Env;

return [
    'driver' => [
        'dsn' => sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            Env::get('DB_HOST', 'pgsql'),
            Env::get('DB_PORT', '5432'),
            Env::get('DB_DATABASE', 'jotup'),
        ),
        'username' => Env::get('DB_USERNAME', 'jotup') ?? '',
        'password' => Env::get('DB_PASSWORD', 'jotup') ?? '',
        'charset' => Env::get('DB_CHARSET', 'utf8'),
        'attributes' => [],
    ],
    'tablePrefix' => Env::get('DB_TABLE_PREFIX', ''),
    'schemaCache' => [
        'enabled' => Env::getBool('DB_SCHEMA_CACHE', true),
        'duration' => (int) (Env::get('DB_SCHEMA_CACHE_DURATION', '3600') ?? '3600'),
        'exclude' => [],
    ],
];
