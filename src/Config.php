<?php

declare(strict_types=1);

namespace Jotup;


use Jotup\Helpers\ArrayHelper;

class Config
{
    private static string $basePath = __DIR__ . '/../config';
    private static array $data;

    public static function get(string $key, $default = null): mixed
    {
        if (!isset(self::$data)) {
            self::load();
        }
        return ArrayHelper::getValue(self::$data, $key, $default);
    }

    private static function load(): void
    {
        $data = [];
        foreach (new \DirectoryIterator(self::$basePath) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            $config = require $fileInfo->getPathname();
            $data[$fileInfo->getBasename('.php')] = $config;
        }
        self::$data = $data;
    }
}