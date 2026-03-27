<?php

declare(strict_types=1);

namespace Jotup\Helpers;

class ArrayHelper
{
    public static function getValue(array $array, $key, $default = null): mixed
    {
        $keys = explode('.', $key);
        if (!$key) {
            return $default;
        }
        return self::getValueRecursive($array, $keys, $default);
    }

    private static function getValueRecursive(array $array, array $keys, $default = null): mixed
    {
        $key = array_shift($keys);
        if (!isset($array[$key])) {
            return $default;
        }
        if (!$keys) {
            return $array[$key];
        }
        if (is_array($array[$key])) {
            return self::getValueRecursive($array[$key], $keys, $default);
        }
        return $default;
    }
}