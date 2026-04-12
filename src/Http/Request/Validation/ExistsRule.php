<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use BackedEnum;
use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;

final class ExistsRule implements RuleInterface
{
    /** @var array<string, mixed> */
    private array $where = [];

    public function __construct(
        private readonly string $table,
        private readonly string $column,
    ) {
    }

    public function where(string $column, mixed $value): self
    {
        $clone = clone $this;
        $clone->where[$column] = $value instanceof BackedEnum ? $value->value : $value;

        return $clone;
    }

    public function name(): string
    {
        return 'exists';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        if ($value === null || $request->db() === null) {
            return;
        }

        $condition = [$this->column => $value];
        foreach ($this->where as $column => $whereValue) {
            $condition[$column] = $whereValue;
        }

        $exists = $request->db()?->query()
            ->from($this->table)
            ->where($condition)
            ->exists();

        if (!$exists) {
            throw new ValidationError(sprintf('The selected %s is invalid.', $field));
        }
    }
}
