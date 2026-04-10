<?php

declare(strict_types=1);

namespace Jotup\ExecutionScope;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class ExecutionScopeLogger implements LoggerInterface
{
    use LoggerTrait;

    public function __construct(
        private readonly LoggerInterface                 $logger,
        private readonly ExecutionScopeProviderInterface $scopeProvider,
    ) {
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $scope = $this->scopeProvider->get();
        if ($scope !== null) {
            $context = [
                'executionScope' => $scope->toArray(),
                ...$context
            ];
        }
        $this->logger->log($level, $message, $context);
    }
}