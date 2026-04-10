<?php

declare(strict_types=1);

namespace Jotup\ExecutionScope;

interface ExecutionScopeProviderInterface
{
    public function setRequestId(string $requestId): void;

    public function setUserId(string $userId): void;

    public function get(): ?ExecutionScope;

    public function set(ExecutionScope $scope): void;

    public function clear(): void;
}