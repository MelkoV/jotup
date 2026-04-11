<?php

declare(strict_types=1);

namespace Jotup\Http\Response;

use Jotup\Http\Exception\HttpException;
use Jotup\Http\Response\Result\EmptyResult;
use Jotup\Http\Response\Result\HtmlResult;
use Jotup\Http\Response\Result\JsonResult;
use Jotup\Http\Response\Result\RedirectResult;

class Respond
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function html(string $content, int $status = 200, array $headers = []): HtmlResult
    {
        return new HtmlResult($content, $status, $headers);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, string|string[]> $headers
     */
    public function json(array $data, int $status = 200, array $headers = []): JsonResult
    {
        return new JsonResult($data, $status, $headers);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function redirect(string $location, int $status = 302, array $headers = []): RedirectResult
    {
        return new RedirectResult($location, $status, $headers);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function noContent(int $status = 204, array $headers = []): EmptyResult
    {
        return new EmptyResult($status, $headers);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function abort(int $status, string $message = '', array $headers = []): never
    {
        throw new HttpException($status, $message, $headers);
    }
}
