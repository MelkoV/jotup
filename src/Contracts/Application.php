<?php

declare(strict_types=1);

namespace Jotup\Contracts;

use Jotup\Container\Container;

interface Application
{
    public function run(): void;

    public function getContainer(): Container;
}