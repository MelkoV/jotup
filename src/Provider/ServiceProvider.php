<?php

declare(strict_types=1);

namespace Jotup\Provider;

use Jotup\Contracts\Application;

interface ServiceProvider
{
    public function register(Application $application): void;
}