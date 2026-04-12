<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckCanViewList implements RuleInterface
{
    public function name(): string
    {
        return 'can_view_list';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): ?string
    {
        $db = $request->db();
        if ($db === null) {
            return null;
        }

        $query = $db->query()
            ->from('{{%lists}}')
            ->where(['id' => $value]);

        $userId = $request->userId();
        if ($userId !== null) {
            $owner = clone $query;
            if ($owner->andWhere(['owner_id' => $userId])->exists()) {
                return null;
            }

            $member = $db->query()
                ->from('{{%list_users}}')
                ->where(['list_id' => $value, 'user_id' => $userId])
                ->exists();

            return $member ? null : 'The selected list is unavailable.';
        }

        return $query->exists() ? null : 'The selected list is unavailable.';
    }
}
