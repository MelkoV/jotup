<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Data\User\JwtTokenData;
use App\Data\User\SignInData;
use App\Data\User\SignUpData;
use App\Enums\JwtTokenType;
use App\Enums\UserDevice;
use App\Exceptions\JwtException;
use App\Exceptions\UserNotFoundException;
use Tests\Support\DatabaseTestCase;

final class UserServiceTest extends DatabaseTestCase
{
    public function testJwtServiceEncodesAndDecodesTokens(): void
    {
        $encoded = new JwtTokenData(
            userId: 'user-1',
            type: JwtTokenType::Refresh,
            time: 3600,
        );

        $token = $this->jwt()->encode($encoded);
        $decoded = $this->jwt()->decode($token);

        $this->assertSame('user-1', $decoded->userId);
        $this->assertSame(JwtTokenType::Refresh, $decoded->type);
    }

    public function testJwtServiceRejectsExpiredTokens(): void
    {
        $token = $this->jwt()->encode(new JwtTokenData(
            userId: 'user-1',
            type: JwtTokenType::Refresh,
            time: 1,
        ));

        sleep(2);

        $this->expectException(JwtException::class);
        $this->expectExceptionMessage('Expired token');

        $this->jwt()->decode($token);
    }

    public function testUserRepositoryCreatesHashedUserAndFindsIt(): void
    {
        $user = $this->users()->create(new SignUpData(
            email: $this->uniqueEmail(),
            password: 'password123',
            name: 'Anton',
            device: UserDevice::Web,
            device_id: 'web-client',
        ));

        $this->assertSame($user->id, $this->users()->findById($user->id)->id);
        $this->assertSame($user->id, $this->users()->findByEmail($user->email)->id);
        $this->assertNotSame('password123', $this->getPasswordHash($user->id));
        $this->assertTrue(password_verify('password123', $this->getPasswordHash($user->id)));
    }

    public function testUserRepositoryThrowsForMissingUser(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->users()->findByEmail('missing@test.test');
    }

    public function testUserServiceSupportsSignUpSignInAndProfile(): void
    {
        $user = $this->userService()->signUp(new SignUpData(
            email: $this->uniqueEmail(),
            password: 'password123',
            name: 'Anton',
            device: UserDevice::Web,
            device_id: 'web-client',
        ));

        $signedIn = $this->userService()->signIn(new SignInData(
            email: $user->email,
            password: 'password123',
            device: UserDevice::Web,
            device_id: 'web-client-2',
        ));

        $profile = $this->userService()->profile($user->id);

        $this->assertSame($user->id, $signedIn->id);
        $this->assertSame($user->id, $profile->id);
        $this->assertSame($user->email, $profile->email);
    }

    public function testUserServiceRejectsInvalidCredentials(): void
    {
        $user = $this->createUser(password: 'password123');

        $this->expectException(UserNotFoundException::class);

        $this->userService()->signIn(new SignInData(
            email: $user->email,
            password: 'wrong-password',
            device: UserDevice::Web,
            device_id: 'web-client',
        ));
    }
}
