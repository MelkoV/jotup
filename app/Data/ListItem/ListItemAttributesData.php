<?php

declare(strict_types=1);

namespace App\Data\ListItem;

use App\Enums\ProductUnit;
use App\Enums\TodoPriority;
use DateTimeInterface;
use Jotup\Data\Data;

class ListItemAttributesData extends Data
{
    public function __construct(
        public readonly ?TodoPriority $priority,
        public readonly ?ProductUnit $unit,
        public readonly ?DateTimeInterface $deadline,
        public readonly ?float $price,
        public readonly ?float $cost,
        public readonly ?float $count,
    ) {
    }

    public function toJson($options = 0): string
    {
        $data = $this->transform();
        $result = array_filter($data, static fn ($value) => $value !== null);
        $json = json_encode($result, $options);
        if ($json === false) {
            throw new \Exception(sprintf('Can not convert ListItemAttributesData to json: %s', json_last_error_msg()));
        }
        return $json;
    }
}
