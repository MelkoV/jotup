<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class M260413120000CreateFeedbackTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $schema = $b->getDb()->getSchema();

        $b->createTable('{{%feedback}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'name' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'email' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'message' => $schema->createColumn(SchemaInterface::TYPE_TEXT)->notNull(),
            'date' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)
                ->notNull()
                ->defaultExpression('NOW()'),
        ]);
        $b->addPrimaryKey('{{%feedback}}', 'pk_feedback', 'id');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('{{%feedback}}');
    }
}
