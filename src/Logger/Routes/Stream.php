<?php

declare(strict_types=1);

namespace Jotup\Logger\Routes;

use Jotup\Exceptions\InvalidArgumentException;
use Jotup\Logger\LogData;

class Stream extends Route
{
    /** @var resource|string */
    protected mixed $stream;

    /**
     * @param resource|string|null $stream
     * @return void
     * @throws InvalidArgumentException
     */
    public function init(mixed $stream = null): void
    {
        if (is_null($stream)) {
            throw new InvalidArgumentException('Stream must be not null');
        }
        $this->stream = $stream;
    }


    public function write(LogData $data): void
    {
        $message = $this->makeMessage($data);

        if (is_resource($this->stream)) {
            fwrite($this->stream, $message . PHP_EOL);
            return;
        }

        file_put_contents($this->stream, $message . PHP_EOL, FILE_APPEND);
    }
}
