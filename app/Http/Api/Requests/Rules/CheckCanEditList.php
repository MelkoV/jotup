<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use App\Enums\ListAccess;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckCanEditList implements RuleInterface
{
    public function name(): string
    {
        return 'can_edit_list';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): ?string
    {
        $db = $request->db();
        if ($db === null) {
            return null;
        }

        $row = $db->query()
            ->from('{{%lists}}')
            ->where(['id' => $value])
            ->one();

        if ($row === null) {
            return 'The selected list is unavailable.';
        }

        $userId = $request->userId();
        if ($userId === null || (string) $row['owner_id'] === $userId) {
            return null;
        }

        $member = $db->query()
            ->from('{{%list_users}}')
            ->where(['list_id' => $value, 'user_id' => $userId])
            ->exists();

        $canEdit = (((int) $row['access']) & ListAccess::CanEdit->value) === ListAccess::CanEdit->value;

        return ($member && $canEdit) ? null : 'You do not have permission to edit this list.';
    }
}
