<?php

declare(strict_types=1);

use Jotup\Env;

$path = APP_CORE_PATH . 'database' . DIRECTORY_SEPARATOR . 'Migrations';

return [
    'historyTable' => Env::get('DB_MIGRATION_HISTORY_TABLE', '{{%migration}}'),
    'migrationNameLimit' => 180,
    'maxSqlOutputLength' => null,
    'useTablePrefix' => true,
    'newMigrationNamespace' => '',
    'newMigrationPath' => $path,
    'sourceNamespaces' => [],
    'sourcePaths' => [$path],
];
