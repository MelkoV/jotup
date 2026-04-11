<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class M260411120000CreateUsersTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $schema = $b->getDb()->getSchema();

        $b->createTable('{{%users}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'name' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'email' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull()->unique(),
            'status' => $schema->createColumn(SchemaInterface::TYPE_STRING, 20)
                ->notNull()
                ->check("status IN ('active', 'blocked')"),
            'avatar' => $schema->createColumn(SchemaInterface::TYPE_STRING)->null(),
            'password' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'email_verified_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'remember_token' => $schema->createColumn(SchemaInterface::TYPE_STRING, 100)->null(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%users}}', 'pk_users', 'id');

        $b->createTable('{{%accounts}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'user_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'device' => $schema->createColumn(SchemaInterface::TYPE_STRING, 20)
                ->notNull()
                ->check("device IN ('web', 'android', 'ios')"),
            'device_id' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'last_login_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)
                ->notNull()
                ->defaultExpression('NOW()'),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%accounts}}', 'pk_accounts', 'id');
        $b->addForeignKey('{{%accounts}}', 'fk_accounts_user_id', 'user_id', '{{%users}}', 'id');
        $b->createIndex('{{%accounts}}', 'ux_accounts_user_device_device_id', ['user_id', 'device', 'device_id'], SchemaInterface::INDEX_UNIQUE);

        $b->createTable('{{%password_reset_tokens}}', [
            'email' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'token' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%password_reset_tokens}}', 'pk_password_reset_tokens', 'email');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('{{%password_reset_tokens}}');
        $b->dropTable('{{%accounts}}');
        $b->dropTable('{{%users}}');
    }
}
