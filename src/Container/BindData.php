<?php

declare(strict_types=1);

namespace Jotup\Container;

readonly final class BindData
{
    public function __construct(
        public string $id,
        public string $concrete,
        public bool $singleton = false,
        public array $values = [],
    ) {}
}