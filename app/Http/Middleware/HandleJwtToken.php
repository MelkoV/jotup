<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\JwtTokenType;
use App\Exceptions\JwtException;
use Psr\Http\Message\ServerRequestInterface;

final class HandleJwtToken extends AbstractHandleJwtToken
{
    protected function getJwtTokenTokenType(): JwtTokenType
    {
        return JwtTokenType::Temporary;
    }

    protected function getToken(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine('Authorization');
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            throw new JwtException('Bearer token is required.');
        }

        return $matches[1];
    }
}
