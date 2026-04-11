<?php

declare(strict_types=1);

namespace App\Enums;

enum DeleteListType: string
{
    case Left = 'left';
    case Delete = 'delete';
}
