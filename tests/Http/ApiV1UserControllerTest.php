<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Contracts\Services\JwtServiceContract;
use App\Contracts\Services\UserServiceContract;
use App\Data\User\JwtTokenData;
use App\Data\User\SignInData;
use App\Data\User\SignUpData;
use App\Data\User\UserData;
use App\Enums\UserDevice;
use App\Enums\UserStatus;
use App\Http\Api\Controllers\UserController;
use Jotup\Container\Container;
use Jotup\Http\Dispatcher\ControllerDispatcher;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;

final class ApiV1UserControllerTest extends TestCase
{
    public function testProfileReadsAuthenticatedUserIdFromRequestAttribute(): void
    {
        $container = new Container();
        $factory = new HttpFactory();

        $container->bind(UserServiceContract::class, new class implements UserServiceContract {
            public function signUp(SignUpData $data): UserData
            {
                throw new \BadMethodCallException();
            }

            public function signIn(SignInData $data): UserData
            {
                throw new \BadMethodCallException();
            }

            public function attachDevice(UserData $user, UserDevice $device, string $deviceId): void
            {
            }

            public function profile(string $userId): UserData
            {
                return new UserData(
                    email: 'user@example.com',
                    name: 'Anton',
                    status: UserStatus::Active,
                    id: $userId,
                );
            }
        });
        $container->bind(JwtServiceContract::class, new class implements JwtServiceContract {
            public function encode(JwtTokenData $data): string
            {
                return 'token';
            }

            public function decode(string $token): JwtTokenData
            {
                throw new \BadMethodCallException();
            }
        });

        $dispatcher = new ControllerDispatcher($container, new Responder($factory, $factory));
        $request = $factory
            ->createServerRequest('GET', '/api/v1/user/profile')
            ->withAttribute('user_id', 'user-123');

        $response = $dispatcher->dispatch(UserController::class, 'profile', [
            'request' => $request,
        ]);

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('user-123', $payload['id']);
        $this->assertSame('user@example.com', $payload['email']);
    }

    public function testLogoutReturnsExpiredRefreshCookie(): void
    {
        $container = new Container();
        $factory = new HttpFactory();

        $container->bind(UserServiceContract::class, new class implements UserServiceContract {
            public function signUp(SignUpData $data): UserData
            {
                throw new \BadMethodCallException();
            }

            public function signIn(SignInData $data): UserData
            {
                throw new \BadMethodCallException();
            }

            public function attachDevice(UserData $user, UserDevice $device, string $deviceId): void
            {
            }

            public function profile(string $userId): UserData
            {
                throw new \BadMethodCallException();
            }
        });
        $container->bind(JwtServiceContract::class, new class implements JwtServiceContract {
            public function encode(JwtTokenData $data): string
            {
                return 'token';
            }

            public function decode(string $token): JwtTokenData
            {
                throw new \BadMethodCallException();
            }
        });

        $dispatcher = new ControllerDispatcher($container, new Responder($factory, $factory));

        $response = $dispatcher->dispatch(UserController::class, 'logout', []);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertStringContainsString('refresh_token=', implode(';', $response->getHeader('Set-Cookie')));
        $this->assertStringContainsString('Max-Age=0', implode(';', $response->getHeader('Set-Cookie')));
    }
}
