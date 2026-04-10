<?php

declare(strict_types=1);

namespace App;

use Jotup\Config;
use Jotup\Container\Container;
use Jotup\ExecutionScope\ExecutionScopeProvider;
use Jotup\ExecutionScope\ExecutionScopeProviderInterface;
use Psr\Log\LoggerInterface;

class Bootstrap implements \Jotup\Application\Bootstrap
{
    public function boot(Container $container): void
    {
//        throw new \Exception('ex');
        $container->bind(ExecutionScopeProviderInterface::class, ExecutionScopeProvider::class, true);
        $loggerBinding = $container->bind(
            id: LoggerInterface::class,
            singleton: true,
            values: Config::get('logger')
        );
        $container->get(LoggerInterface::class)->notice('LoggerInterface::class works');
        $container->bind('logger', $loggerBinding);
        /** @var ExecutionScopeProviderInterface $provider */
        $provider = $container->get(ExecutionScopeProviderInterface::class);
        $provider->setRequestId('1111-123456');
        $provider->setUserId('2222-987654');
        $container->get('logger')->notice('logger alias works');
        $container->get('logger')->notice('Some log...', ['category' => 'test']);
    }

}