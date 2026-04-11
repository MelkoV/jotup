<?php

declare(strict_types=1);

namespace App\Enums;

enum UserDevice: string
{
    case Web = 'web';
    case Android = 'android';
    case Ios = 'ios';
}
