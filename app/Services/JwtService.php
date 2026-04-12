<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\JwtServiceContract;
use App\Data\User\JwtTokenData;
use App\Exceptions\JwtException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Jotup\Config;

final class JwtService implements JwtServiceContract
{
    private string $alg;
    private string $key;

    public function __construct()
    {
        $this->alg = (string) Config::get('jwt.alg', 'HS256');
        $this->key = $this->normalizeKey(
            (string) Config::get('jwt.key', 'dev-jwt-key'),
            $this->alg,
        );
    }

    public function encode(JwtTokenData $data): string
    {
        return JWT::encode($data->toArray(), $this->key, $this->alg);
    }

    public function decode(string $token): JwtTokenData
    {
        try {
            return JwtTokenData::from(JWT::decode($token, new Key($this->key, $this->alg)));
        } catch (\Throwable $e) {
            throw new JwtException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private function normalizeKey(string $key, string $alg): string
    {
        $minimumLength = $this->minimumKeyLength($alg);
        if ($minimumLength === null || strlen($key) >= $minimumLength) {
            return $key;
        }

        // Derive a deterministic development-safe secret from short configured keys.
        return hash('sha512', $key . '|' . $alg);
    }

    private function minimumKeyLength(string $alg): ?int
    {
        return match (strtoupper($alg)) {
            'HS256' => 32,
            'HS384' => 48,
            'HS512' => 64,
            default => null,
        };
    }
}
