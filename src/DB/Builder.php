<?php

declare(strict_types=1);

namespace Jotup\DB;

use Jotup\DB\Connections\Connection;
use Jotup\DI\Container;

class Builder
{
    private Connection $connection;

    public function __construct()
    {
        $this->connection = Container::getComponent('db', Connection::class);
    }

    public function makeQuery(): void
    {
        $this->connection->query();
    }
}