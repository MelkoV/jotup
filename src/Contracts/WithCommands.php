<?php

declare(strict_types=1);

namespace Jotup\Contracts;

interface WithCommands
{
    public function registerCommand(string $command): void;
}
