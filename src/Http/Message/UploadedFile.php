<?php

declare(strict_types=1);

namespace Jotup\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class UploadedFile implements UploadedFileInterface
{
    private bool $moved = false;

    public function __construct(
        private StreamInterface $stream,
        private ?int $size = null,
        private int $error = UPLOAD_ERR_OK,
        private ?string $clientFilename = null,
        private ?string $clientMediaType = null
    ) {
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file has already been moved.');
        }

        return $this->stream;
    }

    public function moveTo(string $targetPath): void
    {
        if ($targetPath === '') {
            throw new InvalidArgumentException('Target path can not be empty.');
        }

        if ($this->moved) {
            throw new RuntimeException('Uploaded file has already been moved.');
        }

        $resource = $this->stream->detach();
        if (!is_resource($resource)) {
            throw new RuntimeException('Uploaded file stream is not available.');
        }

        $target = fopen($targetPath, 'wb');
        if ($target === false) {
            throw new RuntimeException('Unable to open target path for uploaded file.');
        }

        stream_copy_to_stream($resource, $target);
        fclose($resource);
        fclose($target);

        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size ?? $this->stream->getSize();
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}
