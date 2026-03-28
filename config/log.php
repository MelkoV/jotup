<?php

declare(strict_types=1);

use Jotup\Log\Logger;

return [
    'class' => Logger::class,
    'routes' => [
        [
            'class' => 'FileLogger',
        ],
    ],
];