<?php

declare(strict_types=1);

use Psr\Log\LogLevel;

$logger = [
    'routes' => [
        [
            'class' => \Jotup\Logger\Routes\Stream::class,
            'config' => [
                'stream' => 'php://stderr',
            ],
            'exclude' => [\Psr\Log\LogLevel::DEBUG, LogLevel::INFO],
        ],
    ],
];


return $logger;
