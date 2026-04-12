<?php

declare(strict_types=1);

namespace Tests\Support;

use Jotup\Http\Factory\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

abstract class ApiTestCase extends DatabaseTestCase
{
    private HttpFactory $httpFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpFactory = new HttpFactory();
    }

    protected function getJson(string $path, array $query = [], array $headers = [], array $cookies = []): ResponseInterface
    {
        return $this->requestJson('GET', $path, null, $query, $headers, $cookies);
    }

    protected function postJson(string $path, ?array $payload = null, array $headers = [], array $cookies = []): ResponseInterface
    {
        return $this->requestJson('POST', $path, $payload, [], $headers, $cookies);
    }

    protected function putJson(string $path, ?array $payload = null, array $headers = [], array $cookies = []): ResponseInterface
    {
        return $this->requestJson('PUT', $path, $payload, [], $headers, $cookies);
    }

    protected function deleteJson(string $path, ?array $payload = null, array $headers = [], array $cookies = []): ResponseInterface
    {
        return $this->requestJson('DELETE', $path, $payload, [], $headers, $cookies);
    }

    protected function requestJson(
        string $method,
        string $path,
        ?array $payload = null,
        array $query = [],
        array $headers = [],
        array $cookies = [],
    ): ResponseInterface {
        $uri = $path;
        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_X_REQUEST_ID' => Uuid::uuid7()->toString(),
        ];

        $request = $this->httpFactory
            ->createServerRequest($method, $uri, $server)
            ->withQueryParams($query)
            ->withCookieParams($cookies);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($payload !== null) {
            $request = $request->withParsedBody($payload);
        }

        return $this->app->handleRequest($request);
    }

    protected function decodeJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        self::assertNotSame('', $body, 'Response body is empty.');

        $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    protected function withBearer(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }
}
