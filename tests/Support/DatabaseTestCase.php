<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\JwtServiceContract;
use App\Contracts\Services\UserServiceContract;
use App\Data\List\CreateRequestData as CreateListData;
use App\Data\List\ListData;
use App\Data\ListItem\CreateRequestData as CreateListItemData;
use App\Data\ListItem\ListItemData;
use App\Data\User\JwtTokenData;
use App\Data\User\SignUpData;
use App\Data\User\UserData;
use App\Enums\JwtTokenType;
use App\Enums\ListType;
use App\Enums\UserDevice;
use Jotup\Database\Db;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class DatabaseTestCase extends TestCase
{
    protected TestWeb $app;
    protected Db $db;
    private ?TransactionInterface $transaction = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new TestWeb();
        $this->db = $this->app->getContainer()->get(Db::class);
        $this->db->open();
        $this->transaction = $this->db->connection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->transaction !== null) {
                try {
                    $this->transaction->rollBack();
                } catch (\Throwable) {
                }
            }

            $this->db->close();
        } finally {
            restore_exception_handler();
            restore_error_handler();
            parent::tearDown();
        }
    }

    protected function users(): UserRepositoryInterface
    {
        return $this->app->getContainer()->get(UserRepositoryInterface::class);
    }

    protected function lists(): ListRepositoryInterface
    {
        return $this->app->getContainer()->get(ListRepositoryInterface::class);
    }

    protected function userService(): UserServiceContract
    {
        return $this->app->getContainer()->get(UserServiceContract::class);
    }

    protected function jwt(): JwtServiceContract
    {
        return $this->app->getContainer()->get(JwtServiceContract::class);
    }

    protected function createUser(
        ?string $email = null,
        string $password = 'password123',
        string $name = 'Test User',
        UserDevice $device = UserDevice::Web,
        ?string $deviceId = null,
    ): UserData {
        return $this->users()->create(new SignUpData(
            email: $email ?? $this->uniqueEmail(),
            password: $password,
            name: $name,
            device: $device,
            device_id: $deviceId ?? Uuid::uuid7()->toString(),
        ));
    }

    protected function createList(
        string $ownerId,
        string $name = 'Test list',
        bool $isTemplate = false,
        ListType $type = ListType::Shopping,
        ?string $description = 'Test description',
    ): ListData {
        return $this->lists()->create(new CreateListData(
            name: $name,
            owner_id: $ownerId,
            is_template: $isTemplate,
            type: $type,
            description: $description,
        ));
    }

    protected function createListItem(
        string $userId,
        string $listId,
        string $name = 'Test item',
    ): ListItemData {
        return $this->lists()->createListItem(new CreateListItemData(
            user_id: $userId,
            list_id: $listId,
            name: $name,
        ));
    }

    protected function makeJwtToken(string $userId, JwtTokenType $type = JwtTokenType::Temporary, int $time = 900): string
    {
        return $this->jwt()->encode(new JwtTokenData(
            userId: $userId,
            type: $type,
            time: $time,
        ));
    }

    protected function uniqueEmail(): string
    {
        return sprintf('%s@test.test', Uuid::uuid7()->toString());
    }

    protected function getPasswordHash(string $userId): string
    {
        $row = $this->db->query()
            ->from('{{%users}}')
            ->where(['id' => $userId])
            ->one();

        self::assertIsArray($row);

        return (string) $row['password'];
    }
}
