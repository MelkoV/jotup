<?php

declare(strict_types=1);

namespace Jotup\Http\Message;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    /** @var resource|null */
    private $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct($stream = null, string $content = '')
    {
        if ($stream === null) {
            $stream = fopen('php://temp', 'r+');
        }

        if (!is_resource($stream)) {
            throw new RuntimeException('Unable to create stream resource.');
        }

        $this->stream = $stream;

        if ($content !== '') {
            fwrite($this->stream, $content);
            rewind($this->stream);
        }
    }

    public function __toString(): string
    {
        if (!$this->stream) {
            return '';
        }

        $position = ftell($this->stream);
        rewind($this->stream);
        $contents = stream_get_contents($this->stream) ?: '';

        if ($position !== false) {
            fseek($this->stream, $position);
        }

        return $contents;
    }

    public function close(): void
    {
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        return $stream;
    }

    public function getSize(): ?int
    {
        if (!$this->stream) {
            return null;
        }

        $stats = fstat($this->stream);

        return $stats['size'] ?? null;
    }

    public function tell(): int
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached.');
        }

        $position = ftell($this->stream);

        if ($position === false) {
            throw new RuntimeException('Unable to determine stream position.');
        }

        return $position;
    }

    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        if (!$this->stream) {
            return false;
        }

        $meta = stream_get_meta_data($this->stream);

        return (bool)($meta['seekable'] ?? false);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->stream || !$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->stream, $offset, $whence) !== 0) {
            throw new RuntimeException('Unable to seek in stream.');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        if (!$this->stream) {
            return false;
        }

        $mode = (string)(stream_get_meta_data($this->stream)['mode'] ?? '');

        return strpbrk($mode, 'waxc+') !== false;
    }

    public function write(string $string): int
    {
        if (!$this->stream || !$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $written = fwrite($this->stream, $string);

        if ($written === false) {
            throw new RuntimeException('Unable to write to stream.');
        }

        return $written;
    }

    public function isReadable(): bool
    {
        if (!$this->stream) {
            return false;
        }

        $mode = (string)(stream_get_meta_data($this->stream)['mode'] ?? '');

        return strpbrk($mode, 'r+') !== false;
    }

    public function read(int $length): string
    {
        if (!$this->stream || !$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $result = fread($this->stream, $length);

        if ($result === false) {
            throw new RuntimeException('Unable to read from stream.');
        }

        return $result;
    }

    public function getContents(): string
    {
        if (!$this->stream) {
            throw new RuntimeException('Stream is detached.');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream contents.');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        if (!$this->stream) {
            return $key === null ? [] : null;
        }

        $metadata = stream_get_meta_data($this->stream);

        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }
}
