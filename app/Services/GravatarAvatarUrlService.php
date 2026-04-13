<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\AvatarUrlServiceContract;
use App\Data\User\UserData;

final class GravatarAvatarUrlService implements AvatarUrlServiceContract
{
    public function getAvatarUrl(UserData $user): ?string
    {
        $hash = md5(strtolower(trim($user->email)));
        $probeUrl = sprintf('https://www.gravatar.com/avatar/%s?d=404', $hash);

        if (!$this->avatarExists($probeUrl)) {
            return null;
        }

        return sprintf('https://www.gravatar.com/avatar/%s', $hash);
    }

    private function avatarExists(string $url): bool
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 2,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $headers = @get_headers($url, false, $context);
        } catch (\Throwable) {
            return false;
        }

        if (!is_array($headers) || $headers === []) {
            return false;
        }

        $statusLine = (string) $headers[0];

        return str_contains($statusLine, '200');
    }
}
