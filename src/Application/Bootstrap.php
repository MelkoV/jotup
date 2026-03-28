<?php

declare(strict_types=1);

namespace Jotup\Application;

interface Bootstrap
{
    public function boot(): void;

    public function down(): void;
}