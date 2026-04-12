<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Jotup\Config;
use Jotup\Http\Response\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HandleCors implements MiddlewareInterface
{
    public function __construct(
        private readonly Responder $responder,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->matchesConfiguredPath($request)) {
            return $handler->handle($request);
        }

        $origin = trim($request->getHeaderLine('Origin'));
        $headers = $this->buildCorsHeaders($request, $origin);

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->responder->empty(204, $headers);
        }

        $response = $handler->handle($request);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    private function matchesConfiguredPath(ServerRequestInterface $request): bool
    {
        $path = ltrim($request->getUri()->getPath(), '/');
        /** @var list<string> $patterns */
        $patterns = (array) Config::get('cors.paths', []);

        foreach ($patterns as $pattern) {
            $normalizedPattern = ltrim($pattern, '/');

            if ($normalizedPattern === $path) {
                return true;
            }

            if (str_ends_with($normalizedPattern, '/*')) {
                $prefix = substr($normalizedPattern, 0, -1);
                if ($prefix !== '' && str_starts_with($path . '/', $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function buildCorsHeaders(ServerRequestInterface $request, string $origin): array
    {
        $headers = [
            'Vary' => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers',
        ];

        $allowedOrigin = $this->resolveAllowedOrigin($origin);
        if ($allowedOrigin === null) {
            return $headers;
        }

        $headers['Access-Control-Allow-Origin'] = $allowedOrigin;

        if ((bool) Config::get('cors.supports_credentials', false)) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        $methods = (array) Config::get('cors.allowed_methods', ['*']);
        $headers['Access-Control-Allow-Methods'] = $this->stringifyConfiguredList(
            $methods,
            strtoupper($request->getHeaderLine('Access-Control-Request-Method'))
        );

        $allowedHeaders = (array) Config::get('cors.allowed_headers', ['*']);
        $headers['Access-Control-Allow-Headers'] = $this->stringifyConfiguredList(
            $allowedHeaders,
            $request->getHeaderLine('Access-Control-Request-Headers')
        );

        $exposedHeaders = (array) Config::get('cors.exposed_headers', []);
        if ($exposedHeaders !== []) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $exposedHeaders);
        }

        $maxAge = (int) Config::get('cors.max_age', 0);
        if ($maxAge > 0) {
            $headers['Access-Control-Max-Age'] = (string) $maxAge;
        }

        return $headers;
    }

    private function resolveAllowedOrigin(string $origin): ?string
    {
        if ($origin === '') {
            return null;
        }

        /** @var list<string> $allowedOrigins */
        $allowedOrigins = (array) Config::get('cors.allowed_origins', []);

        if (in_array('*', $allowedOrigins, true)) {
            return (bool) Config::get('cors.supports_credentials', false) ? $origin : '*';
        }

        if (in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        /** @var list<string> $patterns */
        $patterns = (array) Config::get('cors.allowed_origins_patterns', []);
        foreach ($patterns as $pattern) {
            if (@preg_match('#' . $pattern . '#', $origin) === 1) {
                return $origin;
            }
        }

        return null;
    }

    /**
     * @param list<string> $values
     */
    private function stringifyConfiguredList(array $values, string $fallback): string
    {
        if (in_array('*', $values, true)) {
            return $fallback !== '' ? $fallback : '*';
        }

        return implode(', ', $values);
    }
}
