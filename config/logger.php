<?php

declare(strict_types=1);

$logger = [
    'routes' => [
        [
            'class' => \Jotup\Logger\Routes\Stream::class,
            'config' => [
                'stream' => 'php://stderr',
            ]
        ],
    ],
];
/*if (APP_DEBUG) {
    $logger['routes'][] = [
        'class' => \Jotup\Logger\Routes\Bootstrap::class,
        'exclude' => [\Psr\Log\LogLevel::DEBUG, \Psr\Log\LogLevel::INFO]
    ];
}*/

return $logger;
