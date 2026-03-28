<?php

declare(strict_types=1);

namespace Jotup\DI;

readonly class BindData
{
    public function __construct(
        public string $concrete,
        public bool $reCreate = false,
        public array $values = [],
    ) {}
}