<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use BackedEnum;
use Jotup\Http\Request\Request;

final readonly class EnumRule implements RuleInterface
{
    /**
     * @param class-string<BackedEnum> $enumClass
     */
    public function __construct(
        private string $enumClass,
    ) {
    }

    public function name(): string
    {
        return 'enum';
    }

    /**
     * @return class-string<BackedEnum>
     */
    public function enumClass(): string
    {
        return $this->enumClass;
    }

    public function validate(string $field, mixed $value, array $data, Request $request): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $this->enumClass) {
            return null;
        }

        try {
            $this->enumClass::from($value);
            return null;
        } catch (\Throwable) {
            return sprintf('The %s field must be a valid enum value.', $field);
        }
    }
}
