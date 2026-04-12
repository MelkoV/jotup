<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Enums\DeleteListType;
use App\Enums\ListFilterTemplate;
use App\Enums\ListType;
use Tests\Support\ApiTestCase;

final class ListsApiTest extends ApiTestCase
{
    public function testCreateListRequiresToken(): void
    {
        $response = $this->postJson('/api/v1/lists', []);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testCreateListRejectsEmptyPayload(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/lists', [], $this->withBearer($this->makeJwtToken($user->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('name', $payload['errors']);
        $this->assertArrayHasKey('type', $payload['errors']);
        $this->assertArrayHasKey('is_template', $payload['errors']);
    }

    public function testCreateListReturnsCreatedModel(): void
    {
        $user = $this->createUser(name: 'Owner');

        $response = $this->postJson('/api/v1/lists', [
            'name' => 'Shopping',
            'is_template' => false,
            'type' => ListType::Shopping->value,
        ], $this->withBearer($this->makeJwtToken($user->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($user->id, $payload['owner_id']);
        $this->assertSame('Owner', $payload['owner_name']);
        $this->assertSame('Shopping', $payload['name']);
        $this->assertFalse($payload['is_template']);
    }

    public function testUpdateListRejectsAccessFromForeignUser(): void
    {
        $owner = $this->createUser();
        $intruder = $this->createUser();
        $list = $this->createList($owner->id);

        $response = $this->putJson(
            '/api/v1/lists/' . $list->id,
            ['name' => 'Updated'],
            $this->withBearer($this->makeJwtToken($intruder->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('id', $payload['errors']);
    }

    public function testUpdateListReturnsUpdatedModel(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id, 'Old name');

        $response = $this->putJson(
            '/api/v1/lists/' . $list->id,
            ['name' => 'New name', 'description' => 'Updated'],
            $this->withBearer($this->makeJwtToken($owner->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($list->id, $payload['id']);
        $this->assertSame('New name', $payload['name']);
        $this->assertSame('Updated', $payload['description']);
    }

    public function testIndexReturnsPaginatedListsAndSupportsTemplateFilter(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $template = $this->createList($owner->id, 'Template', true);
        $this->createList($owner->id, 'Worksheet', false);

        $response = $this->getJson(
            '/api/v1/lists',
            query: [
                'page' => 1,
                'per_page' => 50,
                'template' => ListFilterTemplate::Template->value,
            ],
            headers: $this->withBearer($this->makeJwtToken($owner->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $payload['meta']['current_page']);
        $this->assertSame(50, $payload['meta']['per_page']);
        $this->assertSame(1, $payload['meta']['total']);
        $this->assertCount(1, $payload['data']);
        $this->assertSame($template->id, $payload['data'][0]['id']);
        $this->assertTrue($payload['data'][0]['is_template']);
    }

    public function testViewListRejectsForeignUser(): void
    {
        $owner = $this->createUser();
        $intruder = $this->createUser();
        $list = $this->createList($owner->id);

        $response = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($intruder->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('id', $payload['errors']);
    }

    public function testViewListReturnsModelAndItems(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id, 'Shopping');
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $response = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($list->id, $payload['model']['id']);
        $this->assertSame('Shopping', $payload['model']['name']);
        $this->assertSame($item->id, $payload['items'][0]['id']);
    }

    public function testDeleteTypesReturnsLeftAndDeleteFlags(): void
    {
        $owner = $this->createUser();
        $list = $this->createList($owner->id);

        $response = $this->getJson(
            '/api/v1/lists/delete-types/' . $list->id,
            headers: $this->withBearer($this->makeJwtToken($owner->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload[DeleteListType::Left->value]);
        $this->assertTrue($payload[DeleteListType::Delete->value]);
    }

    public function testLeftListRemovesMembership(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $list = $this->createList($owner->id);
        $this->lists()->addUser($list->id, $member->id);

        $response = $this->deleteJson('/api/v1/lists/left/' . $list->id, headers: $this->withBearer($this->makeJwtToken($member->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($member->id)));
        $this->assertSame(422, $viewResponse->getStatusCode());
    }

    public function testDeleteListRemovesItFromSubsequentReads(): void
    {
        $owner = $this->createUser();
        $list = $this->createList($owner->id);

        $response = $this->deleteJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $this->assertSame(422, $viewResponse->getStatusCode());
    }

    public function testCreateListItemRejectsForeignList(): void
    {
        $owner = $this->createUser();
        $intruder = $this->createUser();
        $list = $this->createList($owner->id);

        $response = $this->postJson('/api/v1/list-items', [
            'name' => 'Milk',
            'list_id' => $list->id,
        ], $this->withBearer($this->makeJwtToken($intruder->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('list_id', $payload['errors']);
    }

    public function testCreateListItemReturnsCreatedItem(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);

        $response = $this->postJson('/api/v1/list-items', [
            'name' => 'Milk',
            'list_id' => $list->id,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($list->id, $payload['list_id']);
        $this->assertSame('Milk', $payload['name']);
        $this->assertSame(1, $payload['version']);
        $this->assertFalse($payload['is_completed']);
    }

    public function testUpdateListItemRejectsWrongVersion(): void
    {
        $owner = $this->createUser();
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id);

        $response = $this->putJson('/api/v1/list-items/' . $item->id, [
            'name' => 'Updated',
            'version' => 2,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('message', $payload);
    }

    public function testUpdateListItemReturnsIncrementedVersion(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $response = $this->putJson('/api/v1/list-items/' . $item->id, [
            'name' => 'Bread',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($item->id, $payload['id']);
        $this->assertSame('Bread', $payload['name']);
        $this->assertSame(2, $payload['version']);
    }

    public function testCompleteListItemMarksItemAsCompleted(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $response = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Milk',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['is_completed']);
        $this->assertSame('Owner', $payload['completed_user_name']);
        $this->assertSame(2, $payload['version']);
    }

    public function testDeleteListItemRemovesItFromView(): void
    {
        $owner = $this->createUser();
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id);

        $response = $this->deleteJson('/api/v1/list-items/' . $item->id, [
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $viewPayload = $this->decodeJson($viewResponse);
        $this->assertSame([], $viewPayload['items']);
    }
}
