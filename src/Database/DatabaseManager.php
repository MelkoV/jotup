<?php

declare(strict_types=1);

namespace Jotup\Database;

use Closure;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final readonly class DatabaseManager
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function open(): void
    {
        $this->connection->open();
    }

    public function close(): void
    {
        $this->connection->close();
    }

    public function createCommand(?string $sql = null, array $params = []): CommandInterface
    {
        return $this->connection->createCommand($sql, $params);
    }

    public function createQuery(): Query
    {
        return new Query($this->connection);
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->connection->getQueryBuilder();
    }

    public function getSchema(): SchemaInterface
    {
        return $this->connection->getSchema();
    }

    /**
     * @throws Throwable
     */
    public function transaction(Closure $callback, ?string $isolationLevel = null): mixed
    {
        return $this->connection->transaction($callback, $isolationLevel);
    }
}
