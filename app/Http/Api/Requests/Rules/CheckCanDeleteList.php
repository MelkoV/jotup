<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckCanDeleteList implements RuleInterface
{
    public function name(): string
    {
        return 'can_delete_list';
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

        return 'Only the list owner can delete this list.';
    }
}
