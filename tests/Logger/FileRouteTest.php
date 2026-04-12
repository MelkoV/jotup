<?php

declare(strict_types=1);

namespace Tests\Logger;

use Jotup\Logger\LogData;
use Jotup\Logger\Routes\File;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

final class FileRouteTest extends TestCase
{
    private string $runtimePath;

    protected function setUp(): void
    {
        $this->runtimePath = APP_CORE_PATH . 'runtime/tests/logger';
        $this->removeDirectory($this->runtimePath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->runtimePath);
    }

    public function testInitCreatesMissingDirectoryAndFile(): void
    {
        $filePath = $this->runtimePath . '/app.log';
        $route = new File();

        $route->init($filePath);
        $route->write(new LogData(LogLevel::INFO, 'created'));

        $this->assertDirectoryExists(dirname($filePath));
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('created', (string) file_get_contents($filePath));
    }

    public function testInitRotatesLargeFileBeforeWriting(): void
    {
        $filePath = $this->runtimePath . '/app.log';
        $rotatedPath = $filePath . '.rotated';

        mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, str_repeat('A', 32));

        $route = new File();
        $route->init($filePath, 16, $rotatedPath);
        $route->write(new LogData(LogLevel::INFO, 'fresh'));

        $this->assertFileExists($rotatedPath);
        $this->assertSame(str_repeat('A', 32), file_get_contents($rotatedPath));
        $this->assertStringContainsString('fresh', (string) file_get_contents($filePath));
        $this->assertStringNotContainsString(str_repeat('A', 32), (string) file_get_contents($filePath));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
