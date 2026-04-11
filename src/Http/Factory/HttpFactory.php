<?php

declare(strict_types=1);

namespace Jotup\Http\Factory;

use Jotup\Http\Message\Stream;
use Jotup\Http\Message\UploadedFile;
use Jotup\Http\Message\Uri;
use Jotup\Http\Request\ClientRequest;
use Jotup\Http\Request\ServerRequest;
use Jotup\Http\Response\Response;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class HttpFactory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->createUri($uri);
        }

        return new ClientRequest($method, $uri);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, reasonPhrase: $reasonPhrase);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->createUri($uri);
        }

        return new ServerRequest(serverParams: $serverParams, method: $method, uri: $uri);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream(content: $content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = fopen($filename, $mode);

        return new Stream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        $parts = parse_url($uri);

        if ($parts === false || $parts === null) {
            return new Uri(path: $uri);
        }

        $userInfo = $parts['user'] ?? '';
        if (isset($parts['pass'])) {
            $userInfo = $userInfo === '' ? ':' . $parts['pass'] : $userInfo . ':' . $parts['pass'];
        }

        return new Uri(
            scheme: $parts['scheme'] ?? '',
            userInfo: $userInfo,
            host: $parts['host'] ?? '',
            port: $parts['port'] ?? null,
            path: $parts['path'] ?? '',
            query: $parts['query'] ?? '',
            fragment: $parts['fragment'] ?? '',
        );
    }
}
