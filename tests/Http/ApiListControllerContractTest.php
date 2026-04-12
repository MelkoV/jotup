<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Contracts\Services\ListServiceContract;
use App\Data\List\CreateRequestData;
use App\Data\List\ListData;
use App\Data\List\ListFilterData;
use App\Data\List\UpdateRequestData;
use App\Data\ListItem\CompleteRequestData;
use App\Data\ListItem\CreateRequestData as CreateListItemRequestData;
use App\Data\ListItem\DeleteRequestData;
use App\Data\ListItem\ListItemData;
use App\Data\ListItem\UpdateRequestData as UpdateListItemRequestData;
use App\Enums\ListType;
use App\Http\Api\Controllers\ListController;
use Jotup\Container\Container;
use Jotup\Http\Dispatcher\ControllerDispatcher;
use Jotup\Http\Factory\HttpFactory;
use Jotup\Http\Response\Responder;
use PHPUnit\Framework\TestCase;

final class ApiListControllerContractTest extends TestCase
{
    public function testIndexReturnsPaginatedPayloadWithMetaWrapper(): void
    {
        $container = new Container();
        $factory = new HttpFactory();

        $container->bind(ListServiceContract::class, new class implements ListServiceContract {
            public function create(CreateRequestData $data): ListData
            {
                throw new \BadMethodCallException();
            }

            public function update(UpdateRequestData $data): ListData
            {
                throw new \BadMethodCallException();
            }

            public function findById(string $id): ListData
            {
                throw new \BadMethodCallException();
            }

            public function delete(string $id): void
            {
            }

            public function leftUser(string $listId, string $userId): void
            {
            }

            public function getFilteredLists(ListFilterData $filter): array
            {
                return [
                    'data' => [
                        new ListData(
                            id: 'list-1',
                            owner_id: '3d594650-b971-4368-b547-a57db6aa98cb',
                            name: 'Groceries',
                            is_template: false,
                            type: ListType::Shopping,
                            owner_name: 'Anton',
                            touched_at: new \DateTime('2026-04-12 12:00:00'),
                            can_edit: true,
                        ),
                    ],
                    'meta' => [
                        'current_page' => 2,
                        'per_page' => 10,
                        'total' => 11,
                        'last_page' => 2,
                        'from' => 11,
                        'to' => 11,
                    ],
                ];
            }

            public function createListItem(CreateListItemRequestData $data): ListItemData
            {
                throw new \BadMethodCallException();
            }

            public function updateListItem(UpdateListItemRequestData $data): ListItemData
            {
                throw new \BadMethodCallException();
            }

            public function completeListItem(CompleteRequestData $data): ListItemData
            {
                throw new \BadMethodCallException();
            }

            public function deleteListItem(DeleteRequestData $data): bool
            {
                throw new \BadMethodCallException();
            }

            public function getListItems(string $listId): array
            {
                throw new \BadMethodCallException();
            }
        });

        $dispatcher = new ControllerDispatcher($container, new Responder($factory, $factory));
        $request = $factory->createServerRequest('GET', '/api/v1/lists?page=2&per_page=10&is_owner=false')
            ->withQueryParams([
                'page' => '2',
                'per_page' => '10',
                'is_owner' => 'false',
            ])
            ->withAttribute('user_id', '3d594650-b971-4368-b547-a57db6aa98cb');

        $response = $dispatcher->dispatch(ListController::class, 'index', [
            'request' => $request,
        ]);

        $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $payload);
        $this->assertArrayHasKey('meta', $payload);
        $this->assertSame('Groceries', $payload['data'][0]['name']);
        $this->assertSame(2, $payload['meta']['current_page']);
        $this->assertSame(11, $payload['meta']['total']);
    }
}
