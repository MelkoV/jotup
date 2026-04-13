<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckListItemIsNotCompleted implements RuleInterface
{
    public function name(): string
    {
        return 'list_item_not_completed';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        $db = $request->db();
        if ($db === null) {
            return;
        }

        $row = $db->query()
            ->select(['is_completed'])
            ->from('{{%list_items}}')
            ->where(['id' => $value])
            ->one();

        if ($row !== null && (bool) ($row['is_completed'] ?? false)) {
            throw new ValidationError('Completed list items cannot be modified.');
        }
    }
}
