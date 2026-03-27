<?php

declare(strict_types=1);

namespace Jotup\DB\Connections;

class PgSQL extends Connection
{



    public function query(): void
    {
        var_dump('PgSQL query handler');
    }
}