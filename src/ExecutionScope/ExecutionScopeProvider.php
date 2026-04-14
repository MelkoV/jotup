<?php

declare(strict_types=1);

namespace Jotup\ExecutionScope;

class ExecutionScopeProvider implements ExecutionScopeProviderInterface
{
    public function setRequestId(string $requestId): void
    {
        $scope = $this->get();

        $this->set(new ExecutionScope(userId: $scope?->userId, requestId: $requestId));
    }

    public function setUserId(string $userId): void
    {
        $scope = $this->get();

        $this->set(new ExecutionScope(userId: $userId, requestId: $scope?->requestId));
    }

    public function get(): ?ExecutionScope
    {
        if ($this->supportsSwooleCoroutineContext()) {
            $context = \OpenSwoole\Coroutine::getContext();
            $scope = $context !== null ? ($context['jotup']['executionScope'] ?? null) : null;

            return $scope instanceof ExecutionScope ? $scope : null;
        }

        return $this->executionScopeStorage['executionScope'] ?? null;
    }

    public function set(ExecutionScope $scope): void
    {
        if ($this->supportsSwooleCoroutineContext()) {
            $context = \OpenSwoole\Coroutine::getContext();

            if ($context !== null) {
                $storage = $context['jotup'] ?? [];
                $storage['executionScope'] = $scope;
                $context['jotup'] = $storage;

                return;
            }
        }

        $this->executionScopeStorage['executionScope'] = $scope;
    }

    public function clear(): void
    {
        if ($this->supportsSwooleCoroutineContext()) {
            $context = \OpenSwoole\Coroutine::getContext();

            if ($context !== null) {
                $storage = $context['jotup'] ?? [];
                unset($storage['executionScope']);

                if ($storage === []) {
                    unset($context['jotup']);
                } else {
                    $context['jotup'] = $storage;
                }

                return;
            }
        }

        unset($this->executionScopeStorage['executionScope']);
    }

    /** @var array<string, mixed> */
    private array $executionScopeStorage = [];

    private function supportsSwooleCoroutineContext(): bool
    {
        if (!extension_loaded('openswoole') && !extension_loaded('swoole')) {
            return false;
        }

        return \OpenSwoole\Coroutine::getCid() > 0;
    }
}
