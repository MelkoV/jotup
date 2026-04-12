<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Bootstrap;
use Jotup\Application\Web;
use Jotup\ExecutionScope\ExecutionScopeLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

final class WebLoggerBootstrapTest extends TestCase
{
    public function testWebBootResolvesScopedLoggerAndAliasToSameInstance(): void
    {
        $application = new Web(new Bootstrap());
        $container = $application->getContainer();

        $logger = $container->get(LoggerInterface::class);
        $aliasLogger = $container->get('logger');

        $this->assertSame($logger, $aliasLogger);

        if (defined('APP_TESTING') && APP_TESTING) {
            $this->assertInstanceOf(NullLogger::class, $logger);

            restore_error_handler();
            restore_exception_handler();

            return;
        }

        $this->assertInstanceOf(ExecutionScopeLogger::class, $logger);

        $reflection = new ReflectionClass($logger);
        $innerLoggerProperty = $reflection->getProperty('logger');
        $innerLogger = $innerLoggerProperty->getValue($logger);

        $this->assertNotSame($logger, $innerLogger);

        $logger->debug('web boot smoke test');

        restore_error_handler();
        restore_exception_handler();
    }
}
