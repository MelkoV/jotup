<?php

declare(strict_types=1);

use Jotup\Log\Logger;

$logger = [
    'class' => Logger::class,
    'routes' => [
        [
            'class' => \Jotup\Log\Routes\Stream::class,
            'config' => [
                'stream' => 'php://stderr',
            ]
        ],
    ],
];
if (APP_DEBUG) {
    $logger['routes'][] = [
        'class' => \Jotup\Log\Routes\Bootstrap::class,
        'exclude' => [\Psr\Log\LogLevel::DEBUG, \Psr\Log\LogLevel::INFO]
    ];
}

return $logger;