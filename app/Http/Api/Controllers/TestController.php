<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use Jotup\Http\Controller\Controller;
use Psr\Log\LoggerInterface;

class TestController extends Controller
{
    public function index(LoggerInterface $logger): array
    {
        $logger->info('Start index action');
        return [
            'ok' => true,
            'message' => 'Public test route works',
        ];
    }

    public function show(string $id): array
    {
        return [
            'ok' => true,
            'message' => 'Protected test route works',
            'id' => $id,
        ];
    }
}
