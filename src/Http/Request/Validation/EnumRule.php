<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use BackedEnum;
use Jotup\Exceptions\ValidationError;
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

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        if ($value === null) {
            return;
        }

        if ($value instanceof $this->enumClass) {
            return;
        }

        try {
            $this->enumClass::from($value);
        } catch (\Throwable) {
            throw new ValidationError(sprintf('The %s field must be a valid enum value.', $field));
        }
    }
}
