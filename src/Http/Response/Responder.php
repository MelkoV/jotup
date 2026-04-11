<?php

declare(strict_types=1);

namespace Jotup\Http\Response;

use Jotup\Http\Exception\HttpException;
use Jotup\Http\Response\Result\EmptyResult;
use Jotup\Http\Response\Result\HtmlResult;
use Jotup\Http\Response\Result\JsonResult;
use Jotup\Http\Response\Result\RedirectResult;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Responder
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {
    }

    public function toResponse(mixed $payload): ResponseInterface
    {
        if ($payload instanceof ResponseInterface) {
            return $payload;
        }

        if ($payload instanceof JsonResult) {
            return $this->json($payload->data, $payload->status, $payload->headers);
        }

        if ($payload instanceof HtmlResult) {
            return $this->html($payload->content, $payload->status, $payload->headers);
        }

        if ($payload instanceof RedirectResult) {
            return $this->redirect($payload->location, $payload->status, $payload->headers);
        }

        if ($payload instanceof EmptyResult || $payload === null) {
            $result = $payload instanceof EmptyResult ? $payload : new EmptyResult();
            return $this->empty($result->status, $result->headers);
        }

        if ($payload instanceof HttpException) {
            return $this->fromHttpException($payload);
        }

        if (is_array($payload)) {
            return $this->json($payload);
        }

        if (is_string($payload)) {
            return $this->html($payload);
        }

        if (is_scalar($payload)) {
            return $this->html((string)$payload);
        }

        return $this->json([
            'ok' => false,
            'error' => 'Unsupported controller result type',
            'type' => get_debug_type($payload),
        ], 500);
    }

    public function fromThrowable(\Throwable $throwable): ResponseInterface
    {
        if ($throwable instanceof HttpException) {
            return $this->fromHttpException($throwable);
        }

        return $this->json([
            'ok' => false,
            'error' => $throwable->getMessage(),
        ], 500);
    }

    /**
     * @param array<mixed> $data
     * @param array<string, string|string[]> $headers
     */
    public function json(array $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $body = $this->streamFactory->createStream((string)json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return $response->withBody($body);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function html(string $content, int $status = 200, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response->withBody($this->streamFactory->createStream($content));
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function redirect(string $location, int $status = 302, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withHeader('Location', $location);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function empty(int $status = 204, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function fromHttpException(HttpException $exception): ResponseInterface
    {
        $status = $exception->getStatusCode();
        $message = $exception->getMessage();

        if ($message === '') {
            return $this->empty($status, $exception->getHeaders());
        }

        return $this->json([
            'ok' => false,
            'error' => $message,
            'status' => $status,
        ], $status, $exception->getHeaders());
    }
}
