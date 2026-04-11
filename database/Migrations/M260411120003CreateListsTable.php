<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class M260411120003CreateListsTable implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $schema = $b->getDb()->getSchema();

        $b->createTable('{{%product_categories}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'name' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'deleted_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%product_categories}}', 'pk_product_categories', 'id');

        $b->createTable('{{%products}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'name' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'unit' => $schema->createColumn(SchemaInterface::TYPE_STRING, 20)
                ->notNull()
                ->check("unit IN ('thing', 'package', 'kg')"),
            'category_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'deleted_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%products}}', 'pk_products', 'id');
        $b->addForeignKey('{{%products}}', 'fk_products_category_id', 'category_id', '{{%product_categories}}', 'id');

        $b->createTable('{{%lists}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'name' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'description' => $schema->createColumn(SchemaInterface::TYPE_STRING)->null(),
            'is_template' => $schema->createColumn(SchemaInterface::TYPE_BOOLEAN)->notNull()->defaultValue(false),
            'type' => $schema->createColumn(SchemaInterface::TYPE_STRING, 20)
                ->notNull()
                ->check("type IN ('shopping', 'todo', 'wishlist')"),
            'touched_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)
                ->notNull()
                ->defaultExpression('NOW()'),
            'short_url' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull()->unique(),
            'access' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull()->defaultValue(1),
            'owner_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'deleted_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%lists}}', 'pk_lists', 'id');
        $b->addForeignKey('{{%lists}}', 'fk_lists_owner_id', 'owner_id', '{{%users}}', 'id');

        $b->createTable('{{%list_users}}', [
            'list_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'user_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%list_users}}', 'pk_list_users', ['list_id', 'user_id']);
        $b->addForeignKey('{{%list_users}}', 'fk_list_users_list_id', 'list_id', '{{%lists}}', 'id');
        $b->addForeignKey('{{%list_users}}', 'fk_list_users_user_id', 'user_id', '{{%users}}', 'id');

        $b->createTable('{{%list_invites}}', [
            'list_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'email' => $schema->createColumn(SchemaInterface::TYPE_STRING)->notNull(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%list_invites}}', 'pk_list_invites', ['list_id', 'email']);
        $b->addForeignKey('{{%list_invites}}', 'fk_list_invites_list_id', 'list_id', '{{%lists}}', 'id');

        $b->createTable('{{%list_items}}', [
            'id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'name' => $schema->createColumn(SchemaInterface::TYPE_STRING)->null(),
            'description' => $schema->createColumn(SchemaInterface::TYPE_STRING)->null(),
            'version' => $schema->createColumn(SchemaInterface::TYPE_INTEGER)->notNull()->defaultValue(1),
            'is_completed' => $schema->createColumn(SchemaInterface::TYPE_BOOLEAN)->notNull()->defaultValue(false),
            'completed_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'completed_user_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->null(),
            'data' => "jsonb NOT NULL DEFAULT '{}'::jsonb",
            'list_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'user_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->notNull(),
            'product_id' => $schema->createColumn(SchemaInterface::TYPE_UUID)->null(),
            'created_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
            'updated_at' => $schema->createColumn(SchemaInterface::TYPE_TIMESTAMP)->null(),
        ]);
        $b->addPrimaryKey('{{%list_items}}', 'pk_list_items', 'id');
        $b->addForeignKey('{{%list_items}}', 'fk_list_items_completed_user_id', 'completed_user_id', '{{%users}}', 'id');
        $b->addForeignKey('{{%list_items}}', 'fk_list_items_list_id', 'list_id', '{{%lists}}', 'id');
        $b->addForeignKey('{{%list_items}}', 'fk_list_items_user_id', 'user_id', '{{%users}}', 'id');
        $b->addForeignKey('{{%list_items}}', 'fk_list_items_product_id', 'product_id', '{{%products}}', 'id');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('{{%list_users}}');
        $b->dropTable('{{%list_invites}}');
        $b->dropTable('{{%list_items}}');
        $b->dropTable('{{%lists}}');
        $b->dropTable('{{%products}}');
        $b->dropTable('{{%product_categories}}');
    }
}
