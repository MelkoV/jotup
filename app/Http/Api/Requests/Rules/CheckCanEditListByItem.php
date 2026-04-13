<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use App\Enums\ListAccess;
use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckCanEditListByItem implements RuleInterface
{
    public function name(): string
    {
        return 'can_edit_list_item';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        $db = $request->db();
        if ($db === null) {
            return;
        }

        $row = $db->query()
            ->select(['li.list_id', 'lists.owner_id', 'lists.access'])
            ->from(['li' => '{{%list_items}}'])
            ->innerJoin(['lists' => '{{%lists}}'], 'lists.[[id]] = li.[[list_id]]')
            ->where(['li.id' => $value, 'lists.deleted_at' => null])
            ->one();

        if ($row === null) {
            throw new ValidationError('The selected list item is unavailable.');
        }

        $userId = $request->userId();
        if ($userId === null || (string) $row['owner_id'] === $userId) {
            return;
        }

        $member = $db->query()
            ->from(['lu' => '{{%list_users}}'])
            ->innerJoin(['lists' => '{{%lists}}'], 'lists.[[id]] = lu.[[list_id]]')
            ->where(['lu.list_id' => $row['list_id'], 'lu.user_id' => $userId, 'lists.deleted_at' => null])
            ->exists();

        $canEdit = (((int) $row['access']) & ListAccess::CanEdit->value) === ListAccess::CanEdit->value;

        if (!$member || !$canEdit) {
            throw new ValidationError('You do not have permission to edit this list item.');
        }
    }
}
