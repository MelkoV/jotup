<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Data\List\ListFilterData;
use App\Data\List\UpdateRequestData;
use App\Data\ListItem\CreateRequestData as CreateListItemData;
use App\Data\ListItem\DeleteRequestData;
use App\Data\ListItem\UpdateRequestData as UpdateListItemData;
use App\Enums\ListFilterTemplate;
use App\Enums\ListType;
use App\Enums\TodoPriority;
use App\Exceptions\ListItemCompletedException;
use App\Exceptions\ListItemNotCompletedException;
use App\Exceptions\ListItemNotFoundException;
use App\Exceptions\ListNotFoundException;
use Tests\Support\DatabaseTestCase;

final class ListRepositoryTest extends DatabaseTestCase
{
    public function testItCreatesUpdatesFiltersAndDeletesLists(): void
    {
        $user = $this->createUser(name: 'Owner');
        $template = $this->createList($user->id, 'Template', true, ListType::Shopping, 'Template description');
        $worksheet = $this->createList($user->id, 'Worksheet', false, ListType::Shopping, 'Worksheet description');

        $updated = $this->lists()->update(new UpdateRequestData(
            id: $worksheet->id,
            name: 'Worksheet updated',
            description: 'Updated description',
        ));

        $this->assertSame($worksheet->id, $updated->id);
        $this->assertSame('Worksheet updated', $updated->name);

        $found = $this->lists()->findById($worksheet->id);
        $this->assertSame('Worksheet updated', $found->name);

        $filtered = $this->lists()->getFilteredLists(new ListFilterData(
            user_id: $user->id,
            page: 1,
            per_page: 10,
            template: ListFilterTemplate::Template,
        ));

        $this->assertCount(1, $filtered['data']);
        $this->assertSame($template->id, $filtered['data'][0]->id);
        $this->assertTrue($filtered['data'][0]->can_edit);
        $this->assertSame(1, $filtered['meta']['total']);
        $this->assertSame(1, $filtered['meta']['last_page']);

        $this->lists()->delete($worksheet->id);

        $this->expectException(ListNotFoundException::class);
        $this->lists()->findById($worksheet->id);
    }

    public function testItManagesListItemsLifecycle(): void
    {
        $user = $this->createUser(name: 'Owner');
        $list = $this->createList($user->id);

        $item = $this->lists()->createListItem(new CreateListItemData(
            user_id: $user->id,
            list_id: $list->id,
            name: 'Milk',
            priority: TodoPriority::High,
            deadline: new \DateTime('2026-04-13'),
        ));
        $this->assertSame('Milk', $item->name);
        $this->assertSame(1, $item->version);
        $this->assertSame(TodoPriority::High, $item->attributes->priority);
        $this->assertStringStartsWith('2026-04-13', $item->attributes->deadline?->format(DATE_ATOM) ?? '');

        $updated = $this->lists()->updateListItem(new UpdateListItemData(
            id: $item->id,
            name: 'Bread',
            version: 1,
        ));
        $this->assertSame('Bread', $updated->name);
        $this->assertSame(2, $updated->version);

        $completed = $this->lists()->completeListItem($item->id, $user->id);
        $this->assertTrue($completed->is_completed);
        $this->assertSame('Owner', $completed->completed_user_name);

        $uncompleted = $this->lists()->uncompleteListItem($item->id);
        $this->assertFalse($uncompleted->is_completed);
        $this->assertNull($uncompleted->completed_user_name);

        $items = $this->lists()->getListItems($list->id);
        $this->assertCount(1, $items);
        $this->assertSame($item->id, $items[0]->id);
    }

    public function testItRejectsStaleListItemVersion(): void
    {
        $user = $this->createUser();
        $list = $this->createList($user->id);
        $item = $this->createListItem($user->id, $list->id, 'Milk');
        $this->lists()->updateListItem(new UpdateListItemData(
            id: $item->id,
            name: 'Bread',
            version: 1,
        ));

        $this->expectException(ListItemNotFoundException::class);

        $this->lists()->updateListItem(new UpdateListItemData(
            id: $item->id,
            name: 'Butter',
            version: 1,
        ));
    }

    public function testItRejectsDeleteAndCompleteForAlreadyCompletedItem(): void
    {
        $user = $this->createUser(name: 'Owner');
        $list = $this->createList($user->id);
        $item = $this->createListItem($user->id, $list->id, 'Milk');

        $completed = $this->lists()->completeListItem($item->id, $user->id);
        $this->assertTrue($completed->is_completed);

        try {
            $this->lists()->completeListItem($item->id, $user->id);
            $this->fail('Expected completed list item completion to be rejected.');
        } catch (ListItemCompletedException $exception) {
            $this->assertSame('Completed list items cannot be modified.', $exception->getMessage());
        }

        $this->expectException(ListItemCompletedException::class);
        $this->expectExceptionMessage('Completed list items cannot be modified.');

        $this->lists()->deleteListItem(new DeleteRequestData(
            id: $item->id,
            version: $completed->version,
        ));
    }

    public function testItRejectsUncompleteForAlreadyOpenItem(): void
    {
        $user = $this->createUser(name: 'Owner');
        $list = $this->createList($user->id);
        $item = $this->createListItem($user->id, $list->id, 'Milk');

        $this->expectException(ListItemNotCompletedException::class);
        $this->expectExceptionMessage('Only completed list items can be restored.');

        $this->lists()->uncompleteListItem($item->id);
    }
}
