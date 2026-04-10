<?php

declare(strict_types=1);

namespace Jotup\ExecutionScope;

class ExecutionScopeProvider implements ExecutionScopeProviderInterface
{
    private ?ExecutionScope $executionScope = null;

    public function setRequestId(string $requestId): void
    {
        $this->set(new ExecutionScope(userId: $this->executionScope?->userId, requestId: $requestId));
    }

    public function setUserId(string $userId): void
    {
        $this->set(new ExecutionScope(userId: $userId, requestId: $this->executionScope?->requestId));
    }

    public function get(): ?ExecutionScope
    {
        return $this->executionScope;
    }

    public function set(ExecutionScope $scope): void
    {
        $this->executionScope = $scope;
    }

    public function clear(): void
    {
        $this->executionScope = null;
    }
}