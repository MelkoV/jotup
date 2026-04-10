<?php

declare(strict_types=1);

$logger = [
    'class' => \Jotup\ExecutionScope\ExecutionScopeLogger::class,
    'logger' => [
        'class' => \Jotup\Logger\Logger::class,
        'routes' => [
            [
                'class' => \Jotup\Logger\Routes\Stream::class,
                'config' => [
                    'stream' => 'php://stderr',
                ]
            ],
        ],
    ],
];
if (APP_DEBUG) {
    $logger['logger']['routes'][] = [
        'class' => \Jotup\Logger\Routes\Bootstrap::class,
        'exclude' => [\Psr\Log\LogLevel::DEBUG, \Psr\Log\LogLevel::INFO]
    ];
}

return $logger;