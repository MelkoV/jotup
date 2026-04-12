<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Data\User\JwtTokenData;
use App\Exceptions\JwtException;

interface JwtServiceContract
{
    public function encode(JwtTokenData $data): string;

    /**
     * @throws JwtException
     */
    public function decode(string $token): JwtTokenData;
}
