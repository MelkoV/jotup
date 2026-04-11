<?php

declare(strict_types=1);

namespace Tests;

use ArrayObject;
use Jotup\ErrorHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class ErrorHandlerTest extends TestCase
{
    public function testIgnoresVendorDeprecationsWhenConfigured(): void
    {
        $records = new ArrayObject();
        $logger = new class ($records) extends AbstractLogger {
            public function __construct(private ArrayObject $records)
            {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };

        $handler = new ErrorHandler(static fn () => $logger, true);

        $ignored = $handler->errorHandler(E_DEPRECATED, 'vendor deprecation', APP_CORE_PATH . 'vendor\\pkg\\file.php', 12);
        $logged = $handler->errorHandler(E_DEPRECATED, 'app deprecation', APP_CORE_PATH . 'src\\Example.php', 15);

        $this->assertTrue($ignored);
        $this->assertTrue($logged);
        $this->assertCount(1, $records);
        $this->assertSame(LogLevel::INFO, $records[0]['level']);
        $this->assertSame('app deprecation', $records[0]['message']);
    }
}
