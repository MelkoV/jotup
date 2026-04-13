<?php

declare(strict_types=1);

namespace App\Http\Api\Requests\Rules;

use App\Enums\ListAccess;
use Jotup\Exceptions\ValidationError;
use Jotup\Http\Request\Request;
use Jotup\Http\Request\Validation\RuleInterface;

final class CheckListHasLinkAccess implements RuleInterface
{
    public function name(): string
    {
        return 'list_has_link_access';
    }

    public function validate(string $field, mixed $value, array $data, Request $request): void
    {
        $db = $request->db();
        if ($db === null) {
            return;
        }

        $row = $db->query()
            ->select(['access'])
            ->from('{{%lists}}')
            ->where(['id' => $value, 'deleted_at' => null])
            ->one();

        if ($row === null) {
            throw new ValidationError('The selected list is unavailable.');
        }

        $hasLinkAccess = (((int) $row['access']) & ListAccess::Link->value) === ListAccess::Link->value;
        if (!$hasLinkAccess) {
            throw new ValidationError('The selected list is unavailable.');
        }
    }
}
