<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckListItemBelongsToNonTemplateList implements RuleInterface
{
    public function name(): string
    {
        return 'list_item_non_template_list';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        $db = $request->db();
        if ($db === null) {
            return;
        }

        $row = $db->query()
            ->select(['lists.is_template'])
            ->from(['li' => '{{%list_items}}'])
            ->innerJoin(['lists' => '{{%lists}}'], 'lists.[[id]] = li.[[list_id]]')
            ->where(['li.id' => $value, 'lists.deleted_at' => null])
            ->one();

        if ($row === null) {
            throw new ValidationError('The selected list item is unavailable.');
        }

        if (!(bool) $row['is_template']) {
            return;
        }

        throw new ValidationError('Template list items cannot be completed.');
    }
}
