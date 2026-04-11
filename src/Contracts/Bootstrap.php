<?php

declare(strict_types=1);

namespace Jotup\Contracts;

use Jotup\Provider\ServiceProvider;

interface Bootstrap
{
    public function boot(Application $application): void;

    /**
     * @return ServiceProvider[]
     */
    public function getServiceProviders(): array;

    public function routes(): array;
}