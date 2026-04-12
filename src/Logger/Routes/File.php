<?php

declare(strict_types=1);

namespace Jotup\Logger\Routes;

use Jotup\Exceptions\InvalidArgumentException;

class File extends Stream
{
    protected string $file;
    protected int $maxFileSize = 10485760;
    protected string $rotatedFile;

    /**
     * @throws InvalidArgumentException
     */
    public function init(
        mixed $file = null,
        int $maxFileSize = 10485760,
        ?string $rotatedFile = null
    ): void {
        if (!is_string($file) || $file === '') {
            throw new InvalidArgumentException('File path must be a non-empty string');
        }

        $this->file = $file;
        $this->maxFileSize = $maxFileSize;
        $this->rotatedFile = $rotatedFile ?? $file . '.1';

        $this->prepareLogFile();

        parent::init($this->file);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function prepareLogFile(): void
    {
        $directory = dirname($this->file);
        if ($directory === '' || $directory === '.') {
            throw new InvalidArgumentException('Unable to resolve log directory');
        }

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Unable to create log directory "%s"', $directory));
        }

        if (!file_exists($this->file) && file_put_contents($this->file, '') === false) {
            throw new InvalidArgumentException(sprintf('Unable to create log file "%s"', $this->file));
        }

        $fileSize = filesize($this->file);
        if ($fileSize !== false && $fileSize >= $this->maxFileSize) {
            $this->rotate();
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function rotate(): void
    {
        if (file_exists($this->rotatedFile) && !unlink($this->rotatedFile)) {
            throw new InvalidArgumentException(sprintf('Unable to remove rotated log file "%s"', $this->rotatedFile));
        }

        if (!rename($this->file, $this->rotatedFile)) {
            throw new InvalidArgumentException(sprintf('Unable to rotate log file "%s"', $this->file));
        }

        if (file_put_contents($this->file, '') === false) {
            throw new InvalidArgumentException(sprintf('Unable to recreate log file "%s"', $this->file));
        }
    }
}
