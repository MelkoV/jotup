<?php

declare(strict_types=1);

namespace Jotup\Http\Request\Validation;

use Jotup\Http\Request\Request;

interface RuleInterface
{
    public function name(): string;

    public function validate(string $field, mixed $value, array $data, Request $request): void;
}
