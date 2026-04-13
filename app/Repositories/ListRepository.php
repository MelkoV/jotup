<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Data\List\CreateRequestData;
use App\Data\List\JoinRequestData;
use App\Data\List\ListData;
use App\Data\List\ListFilterData;
use App\Data\List\ListMemberData;
use App\Data\List\ListPublicInfoData;
use App\Data\List\ShareData;
use App\Data\List\UpdateRequestData;
use App\Data\List\UpdateShareRequestData;
use App\Data\ListItem\CreateRequestData as CreateItemRequestData;
use App\Data\ListItem\DeleteRequestData as DeleteItemRequestData;
use App\Data\ListItem\ListItemAttributesData;
use App\Data\ListItem\ListItemData;
use App\Data\ListItem\UpdateRequestData as UpdateItemRequestData;
use App\Enums\ListAccess;
use App\Enums\ListFilterTemplate;
use App\Exceptions\ListItemCompletedException;
use App\Exceptions\ListItemNotCompletedException;
use App\Exceptions\ListItemNotFoundException;
use App\Exceptions\ListNotFoundException;
use DateTime;
use DateTimeInterface;
use Jotup\Database\Db;
use Ramsey\Uuid\Uuid;

final readonly class ListRepository implements ListRepositoryInterface
{
    public function __construct(
        private Db $db,
    ) {
    }

    public function create(CreateRequestData $data): ListData
    {
        $id = Uuid::uuid7()->toString();
        $now = $this->now();
        $shortUrl = $this->generateUniqueShortUrl();

        $this->db->command()->insert('{{%lists}}', [
            'id' => $id,
            'name' => $data->name,
            'description' => $data->description,
            'is_template' => $data->is_template,
            'type' => $data->type->value,
            'touched_at' => $now,
            'short_url' => $shortUrl,
            'access' => ListAccess::Private->value,
            'owner_id' => $data->owner_id,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        $this->addUser($id, $data->owner_id);

        return $this->findById($id);
    }

    public function update(UpdateRequestData $data): ListData
    {
        $affected = $this->db->command()->update('{{%lists}}', [
            'name' => $data->name,
            'description' => $data->description,
            'touched_at' => $this->now(),
            'updated_at' => $this->now(),
        ], ['id' => $data->id])->execute();

        if ($affected === 0) {
            throw new ListNotFoundException();
        }

        return $this->findById($data->id);
    }

    public function findById(string $id, ?string $currentUserId = null): ListData
    {
        $row = $this->baseListQuery()
            ->andWhere(['lists.id' => $id])
            ->one();

        if ($row === null) {
            throw new ListNotFoundException();
        }

        return $this->hydrateList($row, $currentUserId);
    }

    public function delete(string $id): void
    {
        $this->db->command()->update('{{%lists}}', [
            'deleted_at' => $this->now(),
            'updated_at' => $this->now(),
        ], [
            'id' => $id,
            'deleted_at' => null,
        ])->execute();
    }

    public function leftUser(string $listId, string $userId): void
    {
        $this->db->command()->delete('{{%list_users}}', [
            'list_id' => $listId,
            'user_id' => $userId,
        ])->execute();
    }

    public function addUser(string $listId, string $userId): void
    {
        $now = $this->now();

        $this->db->command()->upsert('{{%list_users}}', [
            'list_id' => $listId,
            'user_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            'updated_at' => $now,
        ])->execute();
    }

    public function touch(string $id): void
    {
        $this->db->command()->update('{{%lists}}', [
            'touched_at' => $this->now(),
            'updated_at' => $this->now(),
        ], ['id' => $id])->execute();
    }

    public function getShareData(string $id): ShareData
    {
        $row = $this->db->query()
            ->select(['short_url', 'access'])
            ->from('{{%lists}}')
            ->where(['id' => $id, 'deleted_at' => null])
            ->one();

        if ($row === null) {
            throw new ListNotFoundException();
        }

        return $this->hydrateShareData($row);
    }

    public function updateShareData(UpdateShareRequestData $data): ShareData
    {
        $access = ListAccess::Private->value;
        if ($data->is_share_link) {
            $access |= ListAccess::Link->value;
        }
        if ($data->can_edit) {
            $access |= ListAccess::CanEdit->value;
        }

        $affected = $this->db->command()->update('{{%lists}}', [
            'access' => $access,
            'updated_at' => $this->now(),
        ], ['id' => $data->id, 'deleted_at' => null])->execute();

        if ($affected === 0) {
            throw new ListNotFoundException();
        }

        return $this->getShareData($data->id);
    }

    public function joinByLink(JoinRequestData $data): ListData
    {
        $this->addUser($data->id, $data->user_id);

        return $this->findById($data->id, $data->user_id);
    }

    public function findPublicInfoByShortUrl(string $shortUrl): ListPublicInfoData
    {
        $row = $this->db->query()
            ->select([
                'lists.id',
                'lists.name',
                'lists.description',
                'owner.name AS owner_name',
                'owner.avatar AS owner_avatar',
            ])
            ->from(['lists' => '{{%lists}}'])
            ->innerJoin(['owner' => '{{%users}}'], 'owner.[[id]] = lists.[[owner_id]]')
            ->where(['lists.short_url' => $shortUrl, 'lists.deleted_at' => null])
            ->one();

        if ($row === null) {
            throw new ListNotFoundException();
        }

        return new ListPublicInfoData(
            id: (string) $row['id'],
            name: (string) $row['name'],
            description: isset($row['description']) ? (string) $row['description'] : null,
            owner_name: (string) $row['owner_name'],
            owner_avatar: isset($row['owner_avatar']) ? (string) $row['owner_avatar'] : null,
        );
    }

    public function copy(string $listId, string $userId, string $name): ListPublicInfoData
    {
        return $this->duplicateList($listId, $userId, $name, false);
    }

    public function createFromTemplate(string $listId, string $userId, string $name): ListPublicInfoData
    {
        return $this->duplicateList($listId, $userId, $name, true);
    }

    public function getFilteredLists(ListFilterData $filter): array
    {
        $query = $this->baseFilteredListsQuery($filter);
        $total = (int) (clone $query)->count('*');

        $items = (clone $query)
            ->orderBy(['lists.touched_at' => SORT_DESC])
            ->limit($filter->per_page)
            ->offset(($filter->page - 1) * $filter->per_page)
            ->all();

        $lastPage = max(1, (int) ceil($total / $filter->per_page));

        return [
            'data' => array_map(
                fn (array $row): ListData => $this->hydrateList($row, $filter->user_id),
                $items
            ),
            'meta' => [
                'current_page' => $filter->page,
                'per_page' => $filter->per_page,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? (($filter->page - 1) * $filter->per_page) + 1 : null,
                'to' => $total > 0 ? (($filter->page - 1) * $filter->per_page) + count($items) : null,
            ],
        ];
    }

    public function createListItem(CreateItemRequestData $data): ListItemData
    {
        $id = Uuid::uuid7()->toString();
        $now = $this->now();
        $attributes = $this->buildItemAttributesPayload($data);

        $this->db->command()->insert('{{%list_items}}', [
            'id' => $id,
            'name' => $data->name,
            'description' => $data->description,
            'version' => 1,
            'is_completed' => false,
            'data' => $attributes,
            'list_id' => $data->list_id,
            'user_id' => $data->user_id,
            'product_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        $this->touch($data->list_id);

        return $this->findListItemView($id);
    }

    public function updateListItem(UpdateItemRequestData $data): ListItemData
    {
        return $this->db->transaction(function () use ($data): ListItemData {
            $item = $this->lockListItemRecord($data->id, $data->version);
            $attributes = $this->buildItemAttributesPayload($data);

            $this->db->command()->update('{{%list_items}}', [
                'name' => $data->name,
                'description' => $data->description,
                'data' => $attributes,
                'version' => $data->version + 1,
                'updated_at' => $this->now(),
            ], ['id' => $data->id])->execute();

            $this->touch((string) $item['list_id']);

            return $this->findListItemView($data->id);
        });
    }

    public function completeListItem(string $listItemId, string $completeUserId): ListItemData
    {
        return $this->db->transaction(function () use ($listItemId, $completeUserId): ListItemData {
            $item = $this->lockListItemRecord($listItemId);

            if ((bool) $item['is_completed']) {
                throw new ListItemCompletedException('Completed list items cannot be modified.');
            }

            $this->db->command()->update('{{%list_items}}', [
                'is_completed' => true,
                'completed_at' => $this->now(),
                'completed_user_id' => $completeUserId,
                'updated_at' => $this->now(),
            ], ['id' => $listItemId])->execute();

            $this->touch((string) $item['list_id']);

            return $this->findListItemView($listItemId);
        });
    }

    public function uncompleteListItem(string $listItemId): ListItemData
    {
        return $this->db->transaction(function () use ($listItemId): ListItemData {
            $item = $this->lockListItemRecord($listItemId);

            if (!(bool) $item['is_completed']) {
                throw new ListItemNotCompletedException('Only completed list items can be restored.');
            }

            $this->db->command()->update('{{%list_items}}', [
                'is_completed' => false,
                'completed_at' => null,
                'completed_user_id' => null,
                'updated_at' => $this->now(),
            ], ['id' => $listItemId])->execute();

            $this->touch((string) $item['list_id']);

            return $this->findListItemView($listItemId);
        });
    }

    public function deleteListItem(DeleteItemRequestData $data): bool
    {
        return $this->db->transaction(function () use ($data): bool {
            $item = $this->lockListItemRecord($data->id, $data->version);
            if ((bool) $item['is_completed']) {
                throw new ListItemCompletedException('Completed list items cannot be modified.');
            }

            $affected = $this->db->command()->delete('{{%list_items}}', [
                'id' => $data->id,
                'version' => $data->version,
            ])->execute();

            $this->touch((string) $item['list_id']);

            return $affected > 0;
        });
    }

    public function getListItems(string $listId): array
    {
        return array_map(
            $this->hydrateListItem(...),
            $this->listItemQuery()
                ->andWhere(['li.list_id' => $listId])
                ->orderBy([
                    'li.is_completed' => SORT_ASC,
                    'li.created_at' => SORT_ASC,
                ])
                ->all()
        );
    }

    public function getListMembers(string $listId): array
    {
        $rows = $this->db->command(
            <<<'SQL'
SELECT
    u."name",
    u."avatar",
    COUNT(*) AS "item_count",
    COALESCE(SUM(COALESCE(NULLIF(li."data"->>'cost', ''), '0')::numeric), 0) AS "sum"
FROM "list_items" li
INNER JOIN "lists" l ON l."id" = li."list_id"
INNER JOIN "users" u ON u."id" = li."completed_user_id"
WHERE li."list_id" = :listId
  AND l."deleted_at" IS NULL
  AND li."completed_user_id" IS NOT NULL
  AND li."is_completed" = TRUE
GROUP BY u."id", u."name", u."avatar"
ORDER BY u."name" ASC
SQL,
            ['listId' => $listId]
        )->queryAll();

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            static fn (array $row): ListMemberData => new ListMemberData(
                name: (string) $row['name'],
                avatar: isset($row['avatar']) ? (string) $row['avatar'] : null,
                item_count: (int) $row['item_count'],
                sum: (float) $row['sum'],
            ),
            $rows,
        );
    }

    private function generateUniqueShortUrl(int $length = 10): string
    {
        do {
            $token = $length
                    |> random_bytes(...)
                    |> base64_encode(...)
                    |> (fn($x) => str_replace(['+', '/', '='], '', $x))
                    |> (fn($x) => substr($x, 0, $length));
            $exists = $this->db->query()
                ->from('{{%lists}}')
                ->where(['short_url' => $token])
                ->exists();

            $length++;
        } while ($exists);

        return $token;
    }

    private function duplicateList(string $listId, string $userId, string $name, bool $forceNonTemplate): ListPublicInfoData
    {
        return $this->db->transaction(function () use ($listId, $userId, $name, $forceNonTemplate): ListPublicInfoData {
            $source = $this->db->query()
                ->from('{{%lists}}')
                ->where(['id' => $listId, 'deleted_at' => null])
                ->one();

            if ($source === null) {
                throw new ListNotFoundException();
            }

            $newListId = Uuid::uuid7()->toString();
            $now = $this->now();

            $this->db->command()->insert('{{%lists}}', [
                'id' => $newListId,
                'name' => $name,
                'description' => $source['description'] ?? null,
                'is_template' => $forceNonTemplate ? false : (bool) $source['is_template'],
                'type' => (string) $source['type'],
                'touched_at' => $now,
                'short_url' => $this->generateUniqueShortUrl(),
                'access' => ListAccess::Private->value,
                'owner_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $this->addUser($newListId, $userId);

            $items = $this->db->query()
                ->select(['li.*'])
                ->from(['li' => '{{%list_items}}'])
                ->innerJoin(['lists' => '{{%lists}}'], 'lists.[[id]] = li.[[list_id]]')
                ->where(['li.list_id' => $listId])
                ->andWhere(['lists.deleted_at' => null])
                ->all();

            foreach ($items as $item) {
                $this->db->command()->insert('{{%list_items}}', [
                    'id' => Uuid::uuid7()->toString(),
                    'name' => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'version' => 1,
                    'is_completed' => false,
                    'completed_at' => null,
                    'completed_user_id' => null,
                    'data' => $this->normalizeItemData($item['data'] ?? []),
                    'list_id' => $newListId,
                    'user_id' => $userId,
                    'product_id' => $item['product_id'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->execute();
            }

            return $this->findPublicInfoById($newListId);
        });
    }

    private function baseListQuery(): \Yiisoft\Db\Query\Query
    {
        return $this->db->query()
            ->select([
                'lists.id',
                'lists.owner_id',
                'lists.name',
                'lists.is_template',
                'lists.type',
                'owner.name AS owner_name',
                'owner.avatar AS owner_avatar',
                'lists.touched_at',
                'lists.description',
                'lists.access',
            ])
            ->from(['lists' => '{{%lists}}'])
            ->innerJoin(['owner' => '{{%users}}'], 'owner.[[id]] = lists.[[owner_id]]')
            ->andWhere(['lists.deleted_at' => null]);
    }

    private function baseFilteredListsQuery(ListFilterData $filter): \Yiisoft\Db\Query\Query
    {
        $query = $this->baseListQuery()
            ->innerJoin('{{%list_users}}', '{{%lists}}.[[id]] = {{%list_users}}.[[list_id]]')
            ->where(['{{%list_users}}.[[user_id]]' => $filter->user_id])
            ->andWhere(['lists.deleted_at' => null]);

        if ($filter->is_owner) {
            $query->andWhere(['lists.owner_id' => $filter->user_id]);
        }

        if ($filter->type !== null) {
            $query->andWhere(['lists.type' => $filter->type->value]);
        }

        if ($filter->template !== null) {
            $query->andWhere([
                'lists.is_template' => $filter->template === ListFilterTemplate::Template,
            ]);
        }

        if ($filter->text !== null && $filter->text !== '') {
            $query->andWhere([
                'or',
                ['like', 'lists.name', $filter->text],
                ['like', 'lists.description', $filter->text],
            ]);
        }

        return $query;
    }

    private function listItemQuery(): \Yiisoft\Db\Query\Query
    {
        return $this->db->query()
            ->select([
                'li.id',
                'creator.name AS user_name',
                'li.list_id',
                'li.version',
                'li.is_completed',
                'li.name',
                'li.description',
                'li.data',
                'creator.avatar AS user_avatar',
                'completed.name AS completed_user_name',
                'completed.avatar AS completed_user_avatar',
            ])
            ->from(['li' => '{{%list_items}}'])
            ->innerJoin(['lists' => '{{%lists}}'], 'lists.[[id]] = li.[[list_id]]')
            ->innerJoin(['creator' => '{{%users}}'], 'creator.[[id]] = li.[[user_id]]')
            ->leftJoin(['completed' => '{{%users}}'], 'completed.[[id]] = li.[[completed_user_id]]')
            ->andWhere(['lists.deleted_at' => null]);
    }

    private function findListItemRecord(string $id, ?int $version = null): array
    {
        $query = $this->db->query()
            ->from('{{%list_items}}')
            ->where(['id' => $id]);

        if ($version !== null) {
            $query->andWhere(['version' => $version]);
        }

        $row = $query->one();

        if ($row === null) {
            throw new ListItemNotFoundException();
        }

        return $row;
    }

    private function lockListItemRecord(string $id, ?int $version = null): array
    {
        $sql = 'SELECT * FROM "list_items" WHERE "id" = :id';
        $params = ['id' => $id];

        if ($version !== null) {
            $sql .= ' AND "version" = :version';
            $params['version'] = $version;
        }

        $sql .= ' FOR UPDATE';
        $row = $this->db->command($sql, $params)->queryOne();

        if (!is_array($row)) {
            throw new ListItemNotFoundException('Похоже, кто-то другой уже изменил этот элемент. Обновите страницу.');
        }

        return $row;
    }

    private function findListItemView(string $id): ListItemData
    {
        $row = $this->listItemQuery()
            ->andWhere(['li.id' => $id])
            ->one();

        if ($row === null) {
            throw new ListItemNotFoundException();
        }

        return $this->hydrateListItem($row);
    }

    private function hydrateList(array $row, ?string $currentUserId = null): ListData
    {
        $ownerId = (string) $row['owner_id'];
        $canEdit = $currentUserId !== null && $currentUserId === $ownerId || (((int)$row['access']) & ListAccess::CanEdit->value) === ListAccess::CanEdit->value;

        return new ListData(
            id: (string) $row['id'],
            owner_id: $ownerId,
            name: (string) $row['name'],
            is_template: (bool) $row['is_template'],
            type: \App\Enums\ListType::from((string) $row['type']),
            owner_name: (string) $row['owner_name'],
            touched_at: new DateTime((string) $row['touched_at']),
            can_edit: $canEdit,
            owner_avatar: isset($row['owner_avatar']) ? (string) $row['owner_avatar'] : null,
            description: isset($row['description']) ? (string) $row['description'] : null,
        );
    }

    private function hydrateListItem(array $row): ListItemData
    {
        $attributes = $row['data'] ?? [];
        if (is_string($attributes)) {
            $decoded = json_decode($attributes, true);
            $attributes = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($attributes)) {
            $attributes = [];
        }

        return new ListItemData(
            id: (string) $row['id'],
            user_name: (string) $row['user_name'],
            list_id: (string) $row['list_id'],
            version: (int) $row['version'],
            is_completed: (bool) $row['is_completed'],
            name: (string) $row['name'],
            attributes: new ListItemAttributesData(
                priority: isset($attributes['priority']) ? \App\Enums\TodoPriority::from((string) $attributes['priority']) : null,
                unit: isset($attributes['unit']) ? \App\Enums\ProductUnit::from((string) $attributes['unit']) : null,
                deadline: isset($attributes['deadline']) ? new DateTime((string) $attributes['deadline']) : null,
                price: isset($attributes['price']) ? (float) $attributes['price'] : null,
                cost: isset($attributes['cost']) ? (float) $attributes['cost'] : null,
                count: isset($attributes['count']) ? (float) $attributes['count'] : null,
            ),
            description: isset($row['description']) ? (string) $row['description'] : null,
            user_avatar: isset($row['user_avatar']) ? (string) $row['user_avatar'] : null,
            completed_user_name: isset($row['completed_user_name']) ? (string) $row['completed_user_name'] : null,
            completed_user_avatar: isset($row['completed_user_avatar']) ? (string) $row['completed_user_avatar'] : null,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateShareData(array $row): ShareData
    {
        $access = (int) $row['access'];

        return new ShareData(
            short_url: (string) $row['short_url'],
            is_share_link: ($access & ListAccess::Link->value) === ListAccess::Link->value,
            can_edit: ($access & ListAccess::CanEdit->value) === ListAccess::CanEdit->value,
        );
    }

    private function findPublicInfoById(string $id): ListPublicInfoData
    {
        $row = $this->db->query()
            ->select([
                'lists.id',
                'lists.name',
                'lists.description',
                'owner.name AS owner_name',
                'owner.avatar AS owner_avatar',
            ])
            ->from(['lists' => '{{%lists}}'])
            ->innerJoin(['owner' => '{{%users}}'], 'owner.[[id]] = lists.[[owner_id]]')
            ->where(['lists.id' => $id, 'lists.deleted_at' => null])
            ->one();

        if ($row === null) {
            throw new ListNotFoundException();
        }

        return new ListPublicInfoData(
            id: (string) $row['id'],
            name: (string) $row['name'],
            description: isset($row['description']) ? (string) $row['description'] : null,
            owner_name: (string) $row['owner_name'],
            owner_avatar: isset($row['owner_avatar']) ? (string) $row['owner_avatar'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeItemData(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildItemAttributesPayload(object $data): array
    {
        $payload = [
            'priority' => $data->priority?->value,
            'unit' => $data->unit?->value,
            'deadline' => $data->deadline?->format(DateTimeInterface::ATOM),
            'price' => $data->price,
            'cost' => $data->cost,
            'count' => $data->count,
        ];

        return array_filter($payload, static fn (mixed $value): bool => $value !== null);
    }

    private function now(): string
    {
        return new DateTime()->format('Y-m-d H:i:s');
    }

}
