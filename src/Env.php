<?php

declare(strict_types=1);

namespace Jotup;

class Env
{
    private static bool $loaded = false;

    private static string $envFile = __DIR__ . '/../.env';

    public static function get(string $key, $default = null): ?string
    {
        if (!self::$loaded) {
            self::load();
        }
        $env = getenv($key);
        if ($env === false) {
            return $default;
        }
        return $env;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $env = self::get($key);
        if ($env === null) {
            return $default;
        }
        if ($env == '1' || strtolower($env) == 'true') {
            return true;
        }
        return false;
    }

    private static function load(): void
    {
        self::$loaded = true;
        $file = self::$envFile;
        if (!file_exists($file)) {
            return;
        }
        $fp = fopen($file, 'r');
        while (($line = fgets($fp)) !== false) {
            $data = explode('=', $line);
            if (isset($data[1])) {
                putenv(sprintf('%s=%s', trim($data[0]), trim($data[1])));
            }
        }
        fclose($fp);
    }
}