<?php

declare(strict_types=1);

$logger = [
    'routes' => [
        [
            'class' => \Jotup\Logger\Routes\File::class,
            'config' => [
                'file' => APP_CORE_PATH . 'runtime/logs/app.log',
            ],
        ],
    ],
];


return $logger;
