<?php

declare(strict_types=1);

namespace Jotup\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Stringable;

class Message implements MessageInterface
{
    protected string $protocolVersion = '1.1';
    /** @var array<string, array<int, string>> */
    protected array $headers = [];
    /** @var array<string, string> */
    protected array $headerNames = [];
    protected StreamInterface $body;

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(array $headers = [], ?StreamInterface $body = null, string $protocolVersion = '1.1')
    {
        $this->body = $body ?? self::createStream();
        $this->protocolVersion = $protocolVersion;

        foreach ($headers as $name => $value) {
            $normalizedName = $this->normalizeHeaderName($name);
            $this->headerNames[strtolower($normalizedName)] = $normalizedName;
            $this->headers[$normalizedName] = $this->normalizeHeaderValue($value);
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $normalizedName = $this->headerNames[strtolower($name)] ?? null;

        if ($normalizedName === null) {
            return [];
        }

        return $this->headers[$normalizedName];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $normalizedName = $this->normalizeHeaderName($name);
        $clone = clone $this;
        $lowerName = strtolower($normalizedName);
        $previousName = $clone->headerNames[$lowerName] ?? null;

        if ($previousName !== null) {
            unset($clone->headers[$previousName]);
        }

        $clone->headerNames[$lowerName] = $normalizedName;
        $clone->headers[$normalizedName] = $this->normalizeHeaderValue($value);

        return $clone;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $normalizedName = $this->normalizeHeaderName($name);
        $clone = clone $this;
        $lowerName = strtolower($normalizedName);
        $existingName = $clone->headerNames[$lowerName] ?? $normalizedName;

        $clone->headerNames[$lowerName] = $existingName;
        $clone->headers[$existingName] = [
            ...($clone->headers[$existingName] ?? []),
            ...$this->normalizeHeaderValue($value),
        ];

        return $clone;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return clone $this;
        }

        $clone = clone $this;
        $lowerName = strtolower($name);
        $normalizedName = $clone->headerNames[$lowerName];

        unset($clone->headerNames[$lowerName], $clone->headers[$normalizedName]);

        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    protected function normalizeHeaderValue(mixed $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (!is_scalar($item) && !$item instanceof Stringable) {
                throw new InvalidArgumentException('Header values must be scalar or stringable.');
            }

            $normalized[] = trim((string)$item);
        }

        return $normalized;
    }

    protected function normalizeHeaderName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Header name can not be empty.');
        }

        return $name;
    }

    protected static function createStream(string $content = ''): StreamInterface
    {
        return new Stream(content: $content);
    }
}
