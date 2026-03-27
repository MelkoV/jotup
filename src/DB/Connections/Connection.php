<?php

declare(strict_types=1);

namespace Jotup\DB\Connections;

abstract class Connection
{
    public function __construct(
        protected string $host,
        protected string $user,
        protected string $password,
        protected string $database,
    )
    {

    }
    abstract public function query(): void;
}