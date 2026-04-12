<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Data\User\JwtTokenData;
use App\Enums\JwtTokenType;
use App\Services\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    public function testShortHs256KeyIsNormalizedForEncodeAndDecode(): void
    {
        $service = new JwtService();
        $token = $service->encode(new JwtTokenData(
            userId: 'user-1',
            type: JwtTokenType::Temporary,
        ));

        $decoded = $service->decode($token);

        $this->assertSame('user-1', $decoded->userId);
        $this->assertSame(JwtTokenType::Temporary, $decoded->type);
    }
}
