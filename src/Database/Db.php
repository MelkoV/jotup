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

final readonly class Db
{
    public function __construct(
        private DatabaseManager $manager,
    ) {
    }

    public function connection(): ConnectionInterface
    {
        return $this->manager->connection();
    }

    public function open(): void
    {
        $this->manager->open();
    }

    public function close(): void
    {
        $this->manager->close();
    }

    public function command(?string $sql = null, array $params = []): CommandInterface
    {
        return $this->manager->createCommand($sql, $params);
    }

    public function query(): Query
    {
        return $this->manager->createQuery();
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->manager->getQueryBuilder();
    }

    public function schema(): SchemaInterface
    {
        return $this->manager->getSchema();
    }

    /**
     * @throws Throwable
     */
    public function transaction(Closure $callback, ?string $isolationLevel = null): mixed
    {
        return $this->manager->transaction($callback, $isolationLevel);
    }
}
