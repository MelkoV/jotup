<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\JwtTokenType;
use App\Exceptions\JwtException;
use Jotup\Config;
use Psr\Http\Message\ServerRequestInterface;

final class HandleRefreshJwtToken extends AbstractHandleJwtToken
{
    protected function getJwtTokenTokenType(): JwtTokenType
    {
        return JwtTokenType::Refresh;
    }

    protected function getToken(ServerRequestInterface $request): string
    {
        $cookieName = (string) Config::get('jwt.cookie.name', 'refresh_token');
        $cookies = $request->getCookieParams();

        if (!isset($cookies[$cookieName]) || !is_string($cookies[$cookieName]) || $cookies[$cookieName] === '') {
            throw new JwtException('Refresh token is required.');
        }

        return $cookies[$cookieName];
    }
}
