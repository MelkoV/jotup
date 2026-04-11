<?php

declare(strict_types=1);

namespace Jotup\Http\Request;

use InvalidArgumentException;
use Jotup\Http\Message\Stream;
use Jotup\Http\Message\UploadedFile;
use Jotup\Http\Message\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends ClientRequest implements ServerRequestInterface
{
    protected array $serverParams = [];
    protected array $cookieParams = [];
    protected array $queryParams = [];
    protected array $uploadedFiles = [];
    protected mixed $parsedBody = null;
    protected array $attributes = [];

    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, string|string[]> $headers
     * @param array<string, mixed> $cookieParams
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $uploadedFiles
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $serverParams = [],
        string $method = 'GET',
        ?UriInterface $uri = null,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1',
        array $cookieParams = [],
        array $queryParams = [],
        array $uploadedFiles = [],
        mixed $parsedBody = null,
        array $attributes = []
    ) {
        parent::__construct($method, $uri, $headers, $body, $protocolVersion);

        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->parsedBody = $parsedBody;
        $this->attributes = $attributes;
    }

    public static function fromGlobals(): self
    {
        $server = $_SERVER;
        $method = is_string($server['REQUEST_METHOD'] ?? null) ? $server['REQUEST_METHOD'] : 'GET';
        $protocol = is_string($server['SERVER_PROTOCOL'] ?? null)
            ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL'])
            : '1.1';

        $uri = new Uri(
            scheme: self::detectScheme($server),
            userInfo: '',
            host: self::detectHost($server),
            port: self::detectPort($server),
            path: self::detectPath($server),
            query: self::detectQuery($server),
            fragment: '',
        );

        $body = new Stream(content: (string)file_get_contents('php://input'));

        return new self(
            serverParams: $server,
            method: $method,
            uri: $uri,
            headers: self::marshalHeaders($server),
            body: $body,
            protocolVersion: $protocol,
            cookieParams: $_COOKIE,
            queryParams: $_GET,
            uploadedFiles: self::marshalUploadedFiles($_FILES),
            parsedBody: self::detectParsedBody($method),
        );
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        if ($data !== null && !is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException('Parsed body must be null, array, or object.');
        }

        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        if (!array_key_exists($name, $this->attributes)) {
            return clone $this;
        }

        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string[]>
     */
    private static function marshalHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $name => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }

            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($name, 5));
                $headerName = ucwords(strtolower($headerName), '-');
                $headers[$headerName] = [(string)$value];
                continue;
            }

            if (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headerName = str_replace('_', '-', $name);
                $headerName = ucwords(strtolower($headerName), '-');
                $headers[$headerName] = [(string)$value];
            }
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $files
     * @return array<string, UploadedFileInterface|array>
     */
    private static function marshalUploadedFiles(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $field => $spec) {
            if (!is_array($spec)) {
                continue;
            }

            $uploadedFiles[$field] = self::createUploadedFileTree($spec);
        }

        return $uploadedFiles;
    }

    /**
     * @param array<string, mixed> $spec
     */
    private static function createUploadedFileTree(array $spec): UploadedFileInterface|array
    {
        if (isset($spec['tmp_name']) && !is_array($spec['tmp_name'])) {
            $tmpName = (string)($spec['tmp_name'] ?? '');
            $resource = $tmpName !== '' && is_file($tmpName) ? fopen($tmpName, 'rb') : fopen('php://temp', 'r+');

            return new UploadedFile(
                new Stream($resource),
                isset($spec['size']) ? (int)$spec['size'] : null,
                (int)($spec['error'] ?? UPLOAD_ERR_OK),
                isset($spec['name']) ? (string)$spec['name'] : null,
                isset($spec['type']) ? (string)$spec['type'] : null,
            );
        }

        $files = [];
        foreach (($spec['tmp_name'] ?? []) as $key => $_) {
            $files[$key] = self::createUploadedFileTree([
                'tmp_name' => $spec['tmp_name'][$key] ?? null,
                'size' => $spec['size'][$key] ?? null,
                'error' => $spec['error'][$key] ?? null,
                'name' => $spec['name'][$key] ?? null,
                'type' => $spec['type'][$key] ?? null,
            ]);
        }

        return $files;
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function detectScheme(array $server): string
    {
        $https = strtolower((string)($server['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return 'https';
        }

        return strtolower((string)($server['REQUEST_SCHEME'] ?? 'http')) ?: 'http';
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function detectHost(array $server): string
    {
        $host = (string)($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? $server['SERVER_ADDR'] ?? '');

        if (str_contains($host, ':')) {
            $parts = explode(':', $host);
            return (string)$parts[0];
        }

        return $host;
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function detectPort(array $server): ?int
    {
        $host = (string)($server['HTTP_HOST'] ?? '');
        if (str_contains($host, ':')) {
            $parts = explode(':', $host);
            return isset($parts[1]) ? (int)$parts[1] : null;
        }

        return isset($server['SERVER_PORT']) ? (int)$server['SERVER_PORT'] : null;
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function detectPath(array $server): string
    {
        $uri = (string)($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function detectQuery(array $server): string
    {
        $query = parse_url((string)($server['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
        if (is_string($query)) {
            return $query;
        }

        return (string)($server['QUERY_STRING'] ?? '');
    }

    private static function detectParsedBody(string $method): mixed
    {
        return in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true) ? $_POST : null;
    }
}
