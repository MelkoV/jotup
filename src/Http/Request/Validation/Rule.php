<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use BackedEnum;

final class Rule
{
    /**
     * @param class-string<BackedEnum> $enumClass
     */
    public static function enum(string $enumClass): EnumRule
    {
        return new EnumRule($enumClass);
    }

    public static function exists(string $table, string $column): ExistsRule
    {
        return new ExistsRule($table, $column);
    }

    public static function unique(string $table, string $column): UniqueRule
    {
        return new UniqueRule($table, $column);
    }
}
