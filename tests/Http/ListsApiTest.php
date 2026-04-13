<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Data\ListItem\CreateRequestData as CreateListItemData;
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
        $this->assertTrue($payload['model']['can_edit']);
        $this->assertSame($item->id, $payload['items'][0]['id']);
        $this->assertSame([], $payload['members']);
    }

    public function testViewListReturnsMembersAggregatedFromExecutors(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $member = $this->createUser(name: 'Alice');
        $list = $this->createList($owner->id, 'Shopping');

        $firstItem = $this->lists()->createListItem(new CreateListItemData(
            user_id: $member->id,
            list_id: $list->id,
            name: 'Milk',
            cost: 10.5,
        ));
        $secondItem = $this->lists()->createListItem(new CreateListItemData(
            user_id: $member->id,
            list_id: $list->id,
            name: 'Bread',
            cost: 15.0,
        ));

        $this->lists()->completeListItem($firstItem->id, $owner->id);
        $this->lists()->completeListItem($secondItem->id, $owner->id);

        $response = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $payload['members']);
        $this->assertSame('Owner', $payload['members'][0]['name']);
        $this->assertNull($payload['members'][0]['avatar']);
        $this->assertSame(2, $payload['members'][0]['item_count']);
        $this->assertSame(25.5, $payload['members'][0]['sum']);
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

    public function testShareReturnsShortUrlAndAccessFlags(): void
    {
        $owner = $this->createUser();
        $list = $this->createList($owner->id);

        $updateResponse = $this->putJson(
            '/api/v1/lists/share/' . $list->id,
            ['is_share_link' => true, 'can_edit' => true],
            $this->withBearer($this->makeJwtToken($owner->id)),
        );
        $this->assertSame(200, $updateResponse->getStatusCode());

        $response = $this->getJson(
            '/api/v1/lists/share/' . $list->id,
            headers: $this->withBearer($this->makeJwtToken($owner->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $payload['short_url']);
        $this->assertTrue($payload['is_share_link']);
        $this->assertTrue($payload['can_edit']);
    }

    public function testShareEndpointsAreAvailableOnlyToOwner(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $member = $this->createUser(name: 'Member');
        $list = $this->createList($owner->id);
        $this->lists()->addUser($list->id, $member->id);

        $getResponse = $this->getJson(
            '/api/v1/lists/share/' . $list->id,
            headers: $this->withBearer($this->makeJwtToken($member->id)),
        );
        $getPayload = $this->decodeJson($getResponse);

        $this->assertSame(422, $getResponse->getStatusCode());
        $this->assertSame('Only the list owner can manage sharing settings.', $getPayload['message']);
        $this->assertArrayHasKey('id', $getPayload['errors']);

        $putResponse = $this->putJson(
            '/api/v1/lists/share/' . $list->id,
            ['is_share_link' => true, 'can_edit' => true],
            $this->withBearer($this->makeJwtToken($member->id)),
        );
        $putPayload = $this->decodeJson($putResponse);

        $this->assertSame(422, $putResponse->getStatusCode());
        $this->assertSame('Only the list owner can manage sharing settings.', $putPayload['message']);
        $this->assertArrayHasKey('id', $putPayload['errors']);
    }

    public function testJoinAddsCurrentUserByLinkAndReturnsList(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $member = $this->createUser(name: 'Member');
        $list = $this->createList($owner->id, 'Shared list');

        $this->putJson(
            '/api/v1/lists/share/' . $list->id,
            ['is_share_link' => true, 'can_edit' => true],
            $this->withBearer($this->makeJwtToken($owner->id)),
        );

        $response = $this->postJson(
            '/api/v1/lists/join/' . $list->id,
            [],
            $this->withBearer($this->makeJwtToken($member->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($list->id, $payload['id']);
        $this->assertSame('Shared list', $payload['name']);
        $this->assertTrue($payload['can_edit']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($member->id)));
        $this->assertSame(200, $viewResponse->getStatusCode());
    }

    public function testJoinRejectsListsWithoutLinkAccess(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $list = $this->createList($owner->id);

        $response = $this->postJson(
            '/api/v1/lists/join/' . $list->id,
            [],
            $this->withBearer($this->makeJwtToken($member->id)),
        );
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('id', $payload['errors']);
    }

    public function testPublicInfoReturnsMinimalListDataByShortUrl(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id, 'Public info list', false, ListType::Shopping, 'Public description');

        $shareResponse = $this->putJson(
            '/api/v1/lists/share/' . $list->id,
            ['is_share_link' => true, 'can_edit' => false],
            $this->withBearer($this->makeJwtToken($owner->id)),
        );
        $sharePayload = $this->decodeJson($shareResponse);

        $response = $this->getJson('/api/v1/lists/info/' . $sharePayload['short_url']);
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($list->id, $payload['id']);
        $this->assertSame('Public info list', $payload['name']);
        $this->assertSame('Public description', $payload['description']);
        $this->assertSame('Owner', $payload['owner_name']);
    }

    public function testPublicInfoReturns404ForUnknownShortUrl(): void
    {
        $response = $this->getJson('/api/v1/lists/info/not-existing-short-url');
        $payload = $this->decodeJson($response);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not found.', $payload['message']);
    }

    public function testCopyCreatesPrivateCopyForCurrentUser(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $member = $this->createUser(name: 'Member');
        $list = $this->createList($owner->id, 'Source list', false, ListType::Shopping, 'Source description');
        $this->lists()->addUser($list->id, $member->id);

        $createResponse = $this->postJson('/api/v1/list-items', [
            'list_id' => $list->id,
            'name' => 'Milk',
            'cost' => '10.500',
            'deadline' => '2026-04-20',
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $createdItem = $this->decodeJson($createResponse);
        $this->assertSame(201, $createResponse->getStatusCode());

        $completeResponse = $this->putJson('/api/v1/list-items/complete/' . $createdItem['id'], [
            'name' => 'Milk',
            'version' => 1,
            'cost' => '10.500',
            'deadline' => '2026-04-20',
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $this->assertSame(200, $completeResponse->getStatusCode());

        $response = $this->postJson('/api/v1/lists/copy/' . $list->id, [
            'name' => 'Copied list',
        ], $this->withBearer($this->makeJwtToken($member->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame($list->id, $payload['id']);
        $this->assertSame('Copied list', $payload['name']);
        $this->assertSame('Source description', $payload['description']);
        $this->assertSame('Member', $payload['owner_name']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $payload['id'], headers: $this->withBearer($this->makeJwtToken($member->id)));
        $viewPayload = $this->decodeJson($viewResponse);

        $this->assertSame(200, $viewResponse->getStatusCode());
        $this->assertFalse($viewPayload['model']['is_template']);
        $this->assertCount(1, $viewPayload['items']);
        $this->assertSame('Milk', $viewPayload['items'][0]['name']);
        $this->assertFalse($viewPayload['items'][0]['is_completed']);
        $this->assertNull($viewPayload['items'][0]['completed_user_name']);
        $this->assertSame(10.5, $viewPayload['items'][0]['attributes']['cost']);
        $this->assertStringStartsWith('2026-04-20', $viewPayload['items'][0]['attributes']['deadline']);
        $this->assertSame([], $viewPayload['members']);

        $copiedItemRow = $this->db->query()
            ->from('{{%list_items}}')
            ->where(['id' => $viewPayload['items'][0]['id']])
            ->one();

        $this->assertIsArray($copiedItemRow);
        $this->assertSame($member->id, $copiedItemRow['user_id']);
        $this->assertNull($copiedItemRow['completed_user_id']);
        $this->assertNull($copiedItemRow['completed_at']);
    }

    public function testCreateFromTemplateCreatesNonTemplateCopyForCurrentUser(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $member = $this->createUser(name: 'Member');
        $list = $this->createList($owner->id, 'Template source', true, ListType::Shopping, 'Template description');
        $this->lists()->addUser($list->id, $member->id);
        $this->createListItem($owner->id, $list->id, 'Milk');

        $response = $this->postJson('/api/v1/lists/create-from-template/' . $list->id, [
            'name' => 'Generated from template',
        ], $this->withBearer($this->makeJwtToken($member->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Generated from template', $payload['name']);
        $this->assertSame('Template description', $payload['description']);
        $this->assertSame('Member', $payload['owner_name']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $payload['id'], headers: $this->withBearer($this->makeJwtToken($member->id)));
        $viewPayload = $this->decodeJson($viewResponse);

        $this->assertSame(200, $viewResponse->getStatusCode());
        $this->assertFalse($viewPayload['model']['is_template']);
        $this->assertCount(1, $viewPayload['items']);
        $this->assertSame('Milk', $viewPayload['items'][0]['name']);
        $this->assertNull($viewPayload['items'][0]['completed_user_name']);
        $this->assertSame([], $viewPayload['members']);
    }

    public function testCompleteListItemRejectsTemplateLists(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id, 'Template', true);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $response = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Milk',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Template list items cannot be completed.', $payload['message']);
        $this->assertArrayHasKey('id', $payload['errors']);
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

        $indexResponse = $this->getJson('/api/v1/lists', query: [
            'is_owner' => true,
            'page' => 1,
            'per_page' => 50,
        ], headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $indexPayload = $this->decodeJson($indexResponse);

        $this->assertSame(200, $indexResponse->getStatusCode());
        $this->assertSame([], $indexPayload['data']);

        $dbRow = $this->db->query()
            ->from('{{%lists}}')
            ->where(['id' => $list->id])
            ->one();

        $this->assertIsArray($dbRow);
        $this->assertNotNull($dbRow['deleted_at']);
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
            'priority' => 'high',
            'deadline' => '2026-04-13',
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($list->id, $payload['list_id']);
        $this->assertSame('Milk', $payload['name']);
        $this->assertSame(1, $payload['version']);
        $this->assertFalse($payload['is_completed']);
        $this->assertSame('high', $payload['attributes']['priority']);
        $this->assertStringStartsWith('2026-04-13', $payload['attributes']['deadline']);
    }

    public function testCreateListItemCanBeCreatedAsCompleted(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);

        $response = $this->postJson('/api/v1/list-items', [
            'name' => 'Milk',
            'list_id' => $list->id,
            'is_completed' => true,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($payload['is_completed']);
        $this->assertSame('Owner', $payload['completed_user_name']);
        $this->assertSame(1, $payload['version']);

        $row = $this->db->query()
            ->from('{{%list_items}}')
            ->where(['id' => $payload['id']])
            ->one();

        $this->assertIsArray($row);
        $this->assertTrue((bool) $row['is_completed']);
        $this->assertSame($owner->id, $row['completed_user_id']);
        $this->assertNotNull($row['completed_at']);
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

    public function testWishlistItemsHideCompletedUserData(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id, 'Wishlist', false, ListType::Wishlist);
        $item = $this->createListItem($owner->id, $list->id, 'Book');

        $response = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Book',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['is_completed']);
        $this->assertNull($payload['completed_user_name']);
        $this->assertNull($payload['completed_user_avatar']);

        $viewResponse = $this->getJson('/api/v1/lists/' . $list->id, headers: $this->withBearer($this->makeJwtToken($owner->id)));
        $viewPayload = $this->decodeJson($viewResponse);

        $this->assertSame(200, $viewResponse->getStatusCode());
        $this->assertNull($viewPayload['items'][0]['completed_user_name']);
        $this->assertNull($viewPayload['items'][0]['completed_user_avatar']);
        $this->assertSame([], $viewPayload['members']);
    }

    public function testUncompleteListItemRestoresOpenState(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $completeResponse = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Milk',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $completePayload = $this->decodeJson($completeResponse);

        $response = $this->putJson('/api/v1/list-items/uncomplete/' . $item->id, [
            'name' => 'Milk',
            'version' => $completePayload['version'],
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($payload['is_completed']);
        $this->assertNull($payload['completed_user_name']);
        $this->assertSame(3, $payload['version']);
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

    public function testCompleteListItemRejectsAlreadyCompletedItem(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $firstResponse = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Milk',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $firstPayload = $this->decodeJson($firstResponse);
        $this->assertSame(200, $firstResponse->getStatusCode());

        $response = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Milk',
            'version' => $firstPayload['version'],
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Completed list items cannot be modified.', $payload['errors']['id'][0]);
    }

    public function testUncompleteListItemRejectsAlreadyOpenItem(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $response = $this->putJson('/api/v1/list-items/uncomplete/' . $item->id, [
            'name' => 'Milk',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Only completed list items can be restored.', $payload['errors']['id'][0]);
    }

    public function testDeleteListItemRejectsAlreadyCompletedItem(): void
    {
        $owner = $this->createUser(name: 'Owner');
        $list = $this->createList($owner->id);
        $item = $this->createListItem($owner->id, $list->id, 'Milk');

        $completeResponse = $this->putJson('/api/v1/list-items/complete/' . $item->id, [
            'name' => 'Milk',
            'version' => 1,
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $completePayload = $this->decodeJson($completeResponse);
        $this->assertSame(200, $completeResponse->getStatusCode());

        $response = $this->deleteJson('/api/v1/list-items/' . $item->id, [
            'version' => $completePayload['version'],
        ], $this->withBearer($this->makeJwtToken($owner->id)));
        $payload = $this->decodeJson($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Completed list items cannot be modified.', $payload['errors']['id'][0]);
    }
}
