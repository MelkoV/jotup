<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Data\User\SignUpData;
use App\Enums\JwtTokenType;
use App\Enums\UserDevice;
use App\Enums\UserStatus;
use Tests\Support\ApiTestCase;

final class UserApiTest extends ApiTestCase
{
    public function testSignUpRejectsEmptyPayload(): void
    {
        $response = $this->postJson('/api/v1/user/sign-up', []);
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('email', $payload['errors']);
        $this->assertArrayHasKey('password', $payload['errors']);
        $this->assertArrayHasKey('repeat_password', $payload['errors']);
        $this->assertArrayHasKey('name', $payload['errors']);
        $this->assertArrayHasKey('device', $payload['errors']);
        $this->assertArrayHasKey('device_id', $payload['errors']);
    }

    public function testSignUpRejectsInvalidEmail(): void
    {
        $response = $this->postJson('/api/v1/user/sign-up', [
            'email' => 'test',
            'password' => 'password123',
            'repeat_password' => 'password123',
            'name' => 'Anton',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client',
        ]);
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('email', $payload['errors']);
    }

    public function testSignUpRejectsPasswordMismatch(): void
    {
        $response = $this->postJson('/api/v1/user/sign-up', [
            'email' => $this->uniqueEmail(),
            'password' => 'password123',
            'repeat_password' => 'password124',
            'name' => 'Anton',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client',
        ]);
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('password', $payload['errors']);
    }

    public function testSignUpCreatesUserAndRefreshCookie(): void
    {
        $email = $this->uniqueEmail();

        $response = $this->postJson('/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'password123',
            'repeat_password' => 'password123',
            'name' => 'Anton',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client',
        ]);
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($email, $payload['user']['email']);
        $this->assertSame('Anton', $payload['user']['name']);
        $this->assertSame(UserStatus::Active->value, $payload['user']['status']);
        $this->assertNull($payload['user']['avatar']);
        $this->assertIsString($payload['token']);
        $this->assertStringContainsString('refresh_token=', $response->getHeaderLine('Set-Cookie'));
    }

    public function testSignUpRejectsDuplicateEmail(): void
    {
        $email = $this->uniqueEmail();

        $first = $this->postJson('/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'password123',
            'repeat_password' => 'password123',
            'name' => 'Anton',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client-1',
        ]);
        $this->assertSame(200, $first->getStatusCode());

        $second = $this->postJson('/api/v1/user/sign-up', [
            'email' => $email,
            'password' => 'password123',
            'repeat_password' => 'password123',
            'name' => 'Anton 2',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client-2',
        ]);
        $payload = $this->decodeJson($second);

        $this->assertSame(422, $second->getStatusCode());
        $this->assertArrayHasKey('email', $payload['errors']);
    }

    public function testSignInRejectsEmptyPayload(): void
    {
        $response = $this->postJson('/api/v1/user/sign-in', []);
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('email', $payload['errors']);
        $this->assertArrayHasKey('password', $payload['errors']);
        $this->assertArrayHasKey('device', $payload['errors']);
        $this->assertArrayHasKey('device_id', $payload['errors']);
    }

    public function testSignInRejectsInvalidDevice(): void
    {
        $user = $this->createUser(password: 'password123');

        $response = $this->postJson('/api/v1/user/sign-in', [
            'email' => $user->email,
            'password' => 'password123',
            'device' => 'desktop',
            'device_id' => 'web-client',
        ]);
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('device', $payload['errors']);
    }

    public function testSignInRejectsIncorrectCredentials(): void
    {
        $user = $this->createUser(password: 'password123');

        $response = $this->postJson('/api/v1/user/sign-in', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client',
        ]);
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('auth.failed', $payload['message']);
        $this->assertSame(['auth.failed'], $payload['errors']['email']);
    }

    public function testSignInReturnsUserAndToken(): void
    {
        $user = $this->createUser(password: 'password123', name: 'Anton');

        $response = $this->postJson('/api/v1/user/sign-in', [
            'email' => $user->email,
            'password' => 'password123',
            'device' => UserDevice::Web->value,
            'device_id' => 'web-client',
        ]);
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user->id, $payload['user']['id']);
        $this->assertSame($user->email, $payload['user']['email']);
        $this->assertSame('Anton', $payload['user']['name']);
        $this->assertNull($payload['user']['avatar']);
        $this->assertIsString($payload['token']);
        $this->assertStringContainsString('refresh_token=', $response->getHeaderLine('Set-Cookie'));
    }

    public function testProfileRejectsMissingToken(): void
    {
        $response = $this->getJson('/api/v1/user/profile');
        $payload = $this->decodeJson($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertArrayHasKey('message', $payload);
    }

    public function testProfileRejectsInvalidToken(): void
    {
        $response = $this->getJson('/api/v1/user/profile', headers: $this->withBearer('fake-token'));
        $payload = $this->decodeJson($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertArrayHasKey('message', $payload);
    }

    public function testProfileRejectsWrongTokenType(): void
    {
        $user = $this->createUser();
        $response = $this->getJson(
            '/api/v1/user/profile',
            headers: $this->withBearer($this->makeJwtToken($user->id, JwtTokenType::Refresh)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Invalid token type', $payload['message']);
    }

    public function testProfileReturnsAuthenticatedUser(): void
    {
        $user = $this->userService()->signUp(new SignUpData(
            email: $this->uniqueEmail(),
            password: 'password123',
            name: 'Anton',
            device: UserDevice::Web,
            device_id: 'profile-device',
        ));

        $response = $this->getJson(
            '/api/v1/user/profile',
            headers: $this->withBearer($this->makeJwtToken($user->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user->id, $payload['id']);
        $this->assertSame($user->email, $payload['email']);
        $this->assertSame('Anton', $payload['name']);
        $this->assertNull($payload['avatar']);
    }

    public function testRefreshTokenRejectsMissingCookie(): void
    {
        $response = $this->postJson('/api/v1/user/refresh-token');
        $payload = $this->decodeJson($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Refresh token is required.', $payload['message']);
    }

    public function testRefreshTokenRejectsWrongTokenType(): void
    {
        $user = $this->createUser();
        $response = $this->postJson(
            '/api/v1/user/refresh-token',
            cookies: ['refresh_token' => $this->makeJwtToken($user->id, JwtTokenType::Temporary)],
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Invalid token type', $payload['message']);
    }

    public function testRefreshTokenReturnsFreshCredentials(): void
    {
        $user = $this->userService()->signUp(new SignUpData(
            email: $this->uniqueEmail(),
            password: 'password123',
            name: 'Anton',
            device: UserDevice::Web,
            device_id: 'refresh-device',
        ));

        $response = $this->postJson(
            '/api/v1/user/refresh-token',
            cookies: ['refresh_token' => $this->makeJwtToken($user->id, JwtTokenType::Refresh)],
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($user->id, $payload['user']['id']);
        $this->assertNull($payload['user']['avatar']);
        $this->assertIsString($payload['token']);
        $this->assertStringContainsString('refresh_token=', $response->getHeaderLine('Set-Cookie'));
    }

    public function testLogoutExpiresCookieWithoutExistingSession(): void
    {
        $response = $this->postJson('/api/v1/user/logout');

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertStringContainsString('Expires=Thu, 01 Jan 1970 00:00:00 GMT', $response->getHeaderLine('Set-Cookie'));
    }

    public function testLogoutExpiresCookieWithRefreshToken(): void
    {
        $user = $this->createUser();

        $response = $this->postJson(
            '/api/v1/user/logout',
            cookies: ['refresh_token' => $this->makeJwtToken($user->id, JwtTokenType::Refresh)],
        );

        $this->assertSame(204, $response->getStatusCode());
        $this->assertStringContainsString('Max-Age=0', $response->getHeaderLine('Set-Cookie'));
    }
}
