<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use Jotup\Http\Response\Result\EmptyResult;
use Jotup\Http\Response\Result\JsonResult;

abstract class Controller
{
    /**
     * @param array<mixed> $data
     * @param array<string, string|string[]> $headers
     */
    protected function json(array $data, int $status = 200, array $headers = []): JsonResult
    {
        return new JsonResult($data, $status, $headers);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    protected function noContent(array $headers = []): EmptyResult
    {
        return new EmptyResult(204, $headers);
    }
}
