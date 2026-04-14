<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Bootstrap;
use Jotup\Application\Web;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TestWeb extends Web
{
    public function __construct()
    {
        parent::__construct(new Bootstrap());
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }
}
