<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\JwtServiceContract;
use App\Data\User\JwtTokenData;
use App\Data\User\UserData;
use App\Enums\JwtTokenType;
use App\Enums\UserStatus;
use App\Http\Middleware\HandleJwtToken;
use App\Http\Middleware\HandleRefreshJwtToken;
use Jotup\Http\Exception\HttpException;
use Jotup\Http\Factory\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JwtMiddlewareTest extends TestCase
{
    public function testBearerMiddlewareAuthenticatesUserAndAddsUserIdToRequest(): void
    {
        $middleware = new HandleJwtToken(
            new class implements JwtServiceContract {
                public function encode(JwtTokenData $data): string
                {
                    return 'token';
                }

                public function decode(string $token): JwtTokenData
                {
                    return new JwtTokenData(userId: 'user-1', type: JwtTokenType::Temporary);
                }
            },
            new class implements UserRepositoryInterface {
                public function create(\App\Data\User\SignUpData $data): UserData
                {
                    throw new \BadMethodCallException();
                }

                public function findById(string $id): UserData
                {
                    return new UserData('user@example.com', 'Anton', UserStatus::Active, $id);
                }

                public function findByEmail(string $email): UserData
                {
                    throw new \BadMethodCallException();
                }

                public function findAuthByEmail(string $email): array
                {
                    throw new \BadMethodCallException();
                }

                public function updateAvatar(UserData $user, ?string $avatar): UserData
                {
                    return new UserData($user->email, $user->name, $user->status, $user->id, $avatar);
                }

                public function upsertDevice(UserData $data, \App\Enums\UserDevice $device, ?string $deviceId = null): void
                {
                }
            },
        );

        $request = (new HttpFactory())->createServerRequest('GET', '/secure')
            ->withHeader('Authorization', 'Bearer access-token');

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $factory = new HttpFactory();
                $response = $factory->createResponse(200);
                $response->getBody()->write((string) $request->getAttribute('user_id'));

                return $response;
            }
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('user-1', (string) $response->getBody());
    }

    public function testRefreshMiddlewareReadsTokenFromCookie(): void
    {
        $middleware = new HandleRefreshJwtToken(
            new class implements JwtServiceContract {
                public function encode(JwtTokenData $data): string
                {
                    return 'token';
                }

                public function decode(string $token): JwtTokenData
                {
                    return new JwtTokenData(userId: 'user-2', type: JwtTokenType::Refresh);
                }
            },
            new class implements UserRepositoryInterface {
                public function create(\App\Data\User\SignUpData $data): UserData
                {
                    throw new \BadMethodCallException();
                }

                public function findById(string $id): UserData
                {
                    return new UserData('user@example.com', 'Anton', UserStatus::Active, $id);
                }

                public function findByEmail(string $email): UserData
                {
                    throw new \BadMethodCallException();
                }

                public function findAuthByEmail(string $email): array
                {
                    throw new \BadMethodCallException();
                }

                public function updateAvatar(UserData $user, ?string $avatar): UserData
                {
                    return new UserData($user->email, $user->name, $user->status, $user->id, $avatar);
                }

                public function upsertDevice(UserData $data, \App\Enums\UserDevice $device, ?string $deviceId = null): void
                {
                }
            },
        );

        $request = (new HttpFactory())->createServerRequest('POST', '/refresh')
            ->withCookieParams(['refresh_token' => 'refresh-token']);

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new HttpFactory())->createResponse(204);
            }
        });

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testBearerMiddlewareRejectsMissingToken(): void
    {
        $middleware = new HandleJwtToken(
            new class implements JwtServiceContract {
                public function encode(JwtTokenData $data): string
                {
                    return 'token';
                }

                public function decode(string $token): JwtTokenData
                {
                    return new JwtTokenData(userId: 'user-1', type: JwtTokenType::Temporary);
                }
            },
            new class implements UserRepositoryInterface {
                public function create(\App\Data\User\SignUpData $data): UserData
                {
                    throw new \BadMethodCallException();
                }

                public function findById(string $id): UserData
                {
                    return new UserData('user@example.com', 'Anton', UserStatus::Active, $id);
                }

                public function findByEmail(string $email): UserData
                {
                    throw new \BadMethodCallException();
                }

                public function findAuthByEmail(string $email): array
                {
                    throw new \BadMethodCallException();
                }

                public function updateAvatar(UserData $user, ?string $avatar): UserData
                {
                    return new UserData($user->email, $user->name, $user->status, $user->id, $avatar);
                }

                public function upsertDevice(UserData $data, \App\Enums\UserDevice $device, ?string $deviceId = null): void
                {
                }
            },
        );

        $this->expectException(HttpException::class);

        $middleware->process(
            (new HttpFactory())->createServerRequest('GET', '/secure'),
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return (new HttpFactory())->createResponse(200);
                }
            }
        );
    }
}
