<?php

declare(strict_types=1);

namespace Jotup\Http\Swoole;

use OpenSwoole\Http\Response as SwooleResponse;
use Psr\Http\Message\ResponseInterface;

final class SwooleResponseEmitter
{
    public function emit(SwooleResponse $target, ResponseInterface $response): void
    {
        $target->status($response->getStatusCode(), $response->getReasonPhrase());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $target->header($name, $value);
            }
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $target->end((string) $body);
    }
}
