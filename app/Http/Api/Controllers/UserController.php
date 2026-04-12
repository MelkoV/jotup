<?php

declare(strict_types=1);

namespace App\Http\Api\Controllers;

use App\Contracts\Services\JwtServiceContract;
use App\Contracts\Services\UserServiceContract;
use App\Data\User\JwtTokenData;
use App\Data\User\UserData;
use App\Enums\JwtTokenType;
use App\Exceptions\UserNotFoundException;
use App\Http\Api\Requests\User\SignInRequest;
use App\Http\Api\Requests\User\SignUpRequest;
use Jotup\Config;
use Psr\Http\Message\ServerRequestInterface;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceContract $userService,
        private readonly JwtServiceContract $jwtService,
    ) {
    }

    public function signUp(SignUpRequest $request): \Jotup\Http\Response\Result\JsonResult
    {
        $user = $this->userService->signUp($request->toData());

        return $this->responseUserDataWithTokens($user);
    }

    public function signIn(SignInRequest $request): \Jotup\Http\Response\Result\JsonResult
    {
        try {
            $user = $this->userService->signIn($request->toData());
        } catch (UserNotFoundException) {
            return $this->json([
                'errors' => ['email' => ['auth.failed']],
                'message' => 'auth.failed',
            ], 422);
        }

        return $this->responseUserDataWithTokens($user);
    }

    public function logout(): \Jotup\Http\Response\Result\EmptyResult
    {
        return $this->noContent([
            'Set-Cookie' => $this->buildRefreshCookie('', true),
        ]);
    }

    public function refreshToken(ServerRequestInterface $request): \Jotup\Http\Response\Result\JsonResult
    {
        $userProfile = $this->userService->profile($this->userId($request));

        return $this->responseUserDataWithTokens($userProfile);
    }

    public function profile(ServerRequestInterface $request): array
    {
        return $this->userService->profile($this->userId($request))->toArray();
    }

    private function responseUserDataWithTokens(UserData $user): \Jotup\Http\Response\Result\JsonResult
    {
        $refreshToken = $this->jwtService->encode(
            new JwtTokenData(
                userId: $user->id,
                type: JwtTokenType::Refresh,
                time: 3600 * 24 * 7,
            )
        );

        return $this->json([
            'user' => $user,
            'token' => $this->jwtService->encode(
                new JwtTokenData(
                    userId: $user->id,
                    type: JwtTokenType::Temporary,
                )
            ),
        ], headers: [
            'Set-Cookie' => $this->buildRefreshCookie($refreshToken),
        ]);
    }

    private function userId(ServerRequestInterface $request): string
    {
        return (string) $request->getAttribute('user_id', '');
    }

    private function buildRefreshCookie(string $value, bool $forget = false): string
    {
        $name = (string) Config::get('jwt.cookie.name', 'refresh_token');
        $domain = Config::get('jwt.cookie.domain');
        $secure = (bool) Config::get('jwt.cookie.secure', true);
        $sameSite = (string) Config::get('jwt.cookie.same_site', 'none');

        $parts = [
            rawurlencode($name) . '=' . rawurlencode($value),
            'Path=/',
            'HttpOnly',
            'SameSite=' . $sameSite,
        ];

        if ($forget) {
            $parts[] = 'Expires=Thu, 01 Jan 1970 00:00:00 GMT';
            $parts[] = 'Max-Age=0';
        } else {
            $parts[] = 'Max-Age=' . (60 * 60 * 24 * 7);
        }

        if (is_string($domain) && $domain !== '') {
            $parts[] = 'Domain=' . $domain;
        }

        if ($secure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }
}
