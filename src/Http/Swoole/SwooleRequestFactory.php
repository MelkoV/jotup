<?php

declare(strict_types=1);

namespace Jotup\Http\Swoole;

use Jotup\Http\Message\Stream;
use Jotup\Http\Message\UploadedFile;
use Jotup\Http\Message\Uri;
use Jotup\Http\Request\ServerRequest;
use OpenSwoole\Http\Request;
use Psr\Http\Message\UploadedFileInterface;

final class SwooleRequestFactory
{
    public function createFromSwoole(Request $request): ServerRequest
    {
        $server = $this->normalizeServerParams($request);
        $rawBody = (string) ($request->rawContent() ?: '');

        return new ServerRequest(
            serverParams: $server,
            method: (string) ($server['REQUEST_METHOD'] ?? 'GET'),
            uri: new Uri(
                scheme: $this->detectScheme($request, $server),
                userInfo: '',
                host: $this->detectHost($request, $server),
                port: $this->detectPort($request, $server),
                path: (string) ($server['REQUEST_URI_PATH'] ?? '/'),
                query: (string) ($server['QUERY_STRING'] ?? ''),
                fragment: '',
            ),
            headers: $this->normalizeHeaders($request),
            body: new Stream(content: $rawBody),
            protocolVersion: $this->detectProtocolVersion($server),
            cookieParams: is_array($request->cookie ?? null) ? $request->cookie : [],
            queryParams: is_array($request->get ?? null) ? $request->get : [],
            uploadedFiles: $this->normalizeUploadedFiles($request->files ?? []),
            parsedBody: $this->detectParsedBody($request, $server, $rawBody),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeServerParams(Request $request): array
    {
        $server = [];

        foreach ((array) ($request->server ?? []) as $name => $value) {
            $normalizedName = strtoupper((string) $name);
            $server[$normalizedName] = $value;
        }

        $requestUri = (string) ($server['REQUEST_URI'] ?? '/');
        $server['REQUEST_URI_PATH'] = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
        $server['QUERY_STRING'] ??= (string) (parse_url($requestUri, PHP_URL_QUERY) ?: '');

        $headers = (array) ($request->header ?? []);
        if (isset($headers['host'])) {
            $server['HTTP_HOST'] = (string) $headers['host'];
        }

        if (isset($headers['content-type'])) {
            $server['CONTENT_TYPE'] = (string) $headers['content-type'];
        }

        if (isset($headers['content-length'])) {
            $server['CONTENT_LENGTH'] = (string) $headers['content-length'];
        }

        foreach ($headers as $name => $value) {
            $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', (string) $name));
            $server[$headerName] = $value;
        }

        return $server;
    }

    /**
     * @return array<string, string[]>
     */
    private function normalizeHeaders(Request $request): array
    {
        $headers = [];

        foreach ((array) ($request->header ?? []) as $name => $value) {
            $headerName = ucwords(strtolower((string) $name), '-');
            $values = is_array($value) ? $value : [$value];
            $headers[$headerName] = array_map(static fn (mixed $item): string => (string) $item, $values);
        }

        return $headers;
    }

    private function detectScheme(Request $request, array $server): string
    {
        $headers = (array) ($request->header ?? []);
        $https = strtolower((string) ($server['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return 'https';
        }

        $forwardedProto = strtolower((string) ($headers['x-forwarded-proto'] ?? ''));
        if ($forwardedProto !== '') {
            return $forwardedProto;
        }

        return strtolower((string) ($server['REQUEST_SCHEME'] ?? 'http')) ?: 'http';
    }

    private function detectHost(Request $request, array $server): string
    {
        $host = (string) ($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? $server['SERVER_ADDR'] ?? '');

        if (str_contains($host, ':')) {
            return (string) explode(':', $host)[0];
        }

        return $host;
    }

    private function detectPort(Request $request, array $server): ?int
    {
        $host = (string) ($server['HTTP_HOST'] ?? '');
        if (str_contains($host, ':')) {
            $parts = explode(':', $host);

            return isset($parts[1]) ? (int) $parts[1] : null;
        }

        return isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;
    }

    private function detectProtocolVersion(array $server): string
    {
        $protocol = (string) ($server['SERVER_PROTOCOL'] ?? 'HTTP/1.1');

        return str_replace('HTTP/', '', $protocol);
    }

    /**
     * @param array<string, mixed>|null $files
     * @return array<string, UploadedFileInterface|array>
     */
    private function normalizeUploadedFiles(?array $files): array
    {
        if (!is_array($files)) {
            return [];
        }

        $uploadedFiles = [];

        foreach ($files as $field => $spec) {
            if (!is_array($spec)) {
                continue;
            }

            $uploadedFiles[(string) $field] = $this->createUploadedFileTree($spec);
        }

        return $uploadedFiles;
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function createUploadedFileTree(array $spec): UploadedFileInterface|array
    {
        if (isset($spec['tmp_name']) && !is_array($spec['tmp_name'])) {
            $tmpName = (string) ($spec['tmp_name'] ?? '');
            $resource = $tmpName !== '' && is_file($tmpName) ? fopen($tmpName, 'rb') : fopen('php://temp', 'r+');

            return new UploadedFile(
                new Stream($resource),
                isset($spec['size']) ? (int) $spec['size'] : null,
                (int) ($spec['error'] ?? UPLOAD_ERR_OK),
                isset($spec['name']) ? (string) $spec['name'] : null,
                isset($spec['type']) ? (string) $spec['type'] : null,
            );
        }

        $files = [];
        foreach ((array) ($spec['tmp_name'] ?? []) as $key => $_) {
            $files[$key] = $this->createUploadedFileTree([
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
    private function detectParsedBody(Request $request, array $server, string $rawBody): mixed
    {
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        if (is_array($request->post ?? null) && $request->post !== []) {
            return $request->post;
        }

        $contentType = strtolower((string) ($server['CONTENT_TYPE'] ?? ''));
        $contentType = trim(explode(';', $contentType, 2)[0]);

        if ($contentType !== 'application/json') {
            return null;
        }

        if (trim($rawBody) === '') {
            return null;
        }

        $decoded = json_decode($rawBody, true);

        return is_array($decoded) ? $decoded : null;
    }
}
