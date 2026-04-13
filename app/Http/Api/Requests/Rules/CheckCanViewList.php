<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckCanViewList implements RuleInterface
{
    public function name(): string
    {
        return 'can_view_list';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        $db = $request->db();
        if ($db === null) {
            return;
        }

        $query = $db->query()
            ->from('{{%lists}}')
            ->where(['id' => $value, 'deleted_at' => null]);

        $userId = $request->userId();
        if ($userId !== null) {
            $owner = clone $query;
            if ($owner->andWhere(['owner_id' => $userId])->exists()) {
                return;
            }

            $member = $db->query()
                ->from(['lu' => '{{%list_users}}'])
                ->innerJoin(['lists' => '{{%lists}}'], 'lists.[[id]] = lu.[[list_id]]')
                ->where(['lu.list_id' => $value, 'lu.user_id' => $userId, 'lists.deleted_at' => null])
                ->exists();

            if (!$member) {
                throw new ValidationError('The selected list is unavailable.');
            }

            return;
        }

        if (!$query->exists()) {
            throw new ValidationError('The selected list is unavailable.');
        }
    }
}
