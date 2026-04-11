<?php

declare(strict_types=1);

namespace Jotup\Http\Request;

use InvalidArgumentException;
use Jotup\Http\Message\Message;
use Jotup\Http\Message\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ClientRequest extends Message implements RequestInterface
{
    protected string $requestTarget = '/';
    protected string $method = 'GET';
    protected UriInterface $uri;

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        string $method = 'GET',
        ?UriInterface $uri = null,
        array $headers = [],
        ?StreamInterface $body = null,
        string $protocolVersion = '1.1'
    ) {
        parent::__construct($headers, $body, $protocolVersion);

        $method = strtoupper(trim($method));
        if ($method === '') {
            throw new InvalidArgumentException('HTTP method can not be empty.');
        }

        $this->method = $method;
        $this->uri = $uri ?? new Uri();
        $this->requestTarget = $this->buildRequestTarget($this->uri);

        if ($this->uri->getHost() !== '' && !$this->hasHeader('Host')) {
            $hostHeader = $this->uri->getPort() === null
                ? $this->uri->getHost()
                : $this->uri->getHost() . ':' . $this->uri->getPort();
            $this->headers['Host'] = [$hostHeader];
            $this->headerNames['host'] = 'Host';
        }
    }

    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if (preg_match('/\s/', $requestTarget) === 1) {
            throw new InvalidArgumentException('Request target must not contain whitespace.');
        }

        $clone = clone $this;
        $clone->requestTarget = $requestTarget === '' ? '/' : $requestTarget;

        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $method = strtoupper(trim($method));
        if ($method === '') {
            throw new InvalidArgumentException('HTTP method can not be empty.');
        }

        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = $uri;
        $clone->requestTarget = $this->buildRequestTarget($uri);

        $host = $uri->getHost();
        if ($host !== '' && (!$preserveHost || !$clone->hasHeader('Host'))) {
            $hostHeader = $uri->getPort() === null ? $host : $host . ':' . $uri->getPort();
            $clone = $clone->withHeader('Host', $hostHeader);
        }

        return $clone;
    }

    protected function buildRequestTarget(UriInterface $uri): string
    {
        $path = $uri->getPath();
        $query = $uri->getQuery();

        if ($path === '') {
            $path = '/';
        }

        return $query === '' ? $path : $path . '?' . $query;
    }
}
