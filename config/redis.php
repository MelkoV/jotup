<?php

declare(strict_types=1);

use Jotup\Env;

return [
    'host' => (string) Env::get('REDIS_HOST', '127.0.0.1'),
    'port' => (int) Env::get('REDIS_PORT', '6379'),
    'password' => Env::get('REDIS_PASSWORD'),
    'database' => (int) Env::get('REDIS_DB', '0'),
    'timeout' => (float) Env::get('REDIS_TIMEOUT', '2'),
    'read_timeout' => (float) Env::get('REDIS_READ_TIMEOUT', '15'),
    'queues' => [
        'avatar' => (string) Env::get('REDIS_QUEUE_AVATAR', 'avatar:sync'),
    ],
];
