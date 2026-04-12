<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use BackedEnum;
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

    public function validate(string $field, mixed $value, array $data, Request $request): ?string
    {
        if ($value === null || $request->db() === null) {
            return null;
        }

        $condition = [$this->column => $value];
        foreach ($this->where as $column => $whereValue) {
            $condition[$column] = $whereValue;
        }

        $exists = $request->db()?->query()
            ->from($this->table)
            ->where($condition)
            ->exists();

        return $exists ? null : sprintf('The selected %s is invalid.', $field);
    }
}
