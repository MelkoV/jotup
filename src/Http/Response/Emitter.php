<?php

declare(strict_types=1);

namespace Jotup\Http\Response;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class Emitter
{
    public function emit(ResponseInterface $response): void
    {
        if (headers_sent($file, $line)) {
            throw new RuntimeException(sprintf('Headers already sent in %s on line %d.', $file, $line));
        }

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            $replace = strcasecmp($name, 'Set-Cookie') !== 0;

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $replace);
                $replace = false;
            }
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }
}
