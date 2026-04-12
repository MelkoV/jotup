<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;

final readonly class UniqueRule implements RuleInterface
{
    public function __construct(
        private string $table,
        private string $column,
    ) {
    }

    public function name(): string
    {
        return 'unique';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        if ($value === null || $request->db() === null) {
            return;
        }

        $exists = $request->db()?->query()
            ->from($this->table)
            ->where([$this->column => $value])
            ->exists();

        if ($exists) {
            throw new ValidationError(sprintf('The %s has already been taken.', $field));
        }
    }
}
