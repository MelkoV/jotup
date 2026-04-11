<?php

declare(strict_types=1);

namespace Jotup\Http\Controller;

use Jotup\Http\Response\Respond;

abstract class Controller
{
    public function __construct(
        protected readonly Respond $respond
    ) {
    }
}
