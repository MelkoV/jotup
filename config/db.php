<?php

declare(strict_types=1);

return [
    'class' => \Jotup\DB\Connections\PgSQL::class,
    'host' => getenv('DB_HOST'),
    'user' => getenv('DB_USERNAME'),
    'password' => getenv('DB_PASSWORD'),
    'database' => getenv('DB_DATABASE'),
];