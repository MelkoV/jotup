<?php

declare(strict_types=1);

namespace Tests\Application;

use Jotup\Application\Web;
use Jotup\Contracts\Application;
use Jotup\Contracts\Bootstrap;
use PHPUnit\Framework\TestCase;

final class WebRoutePrefixTest extends TestCase
{
    private array $filesToDelete = [];

    protected function tearDown(): void
    {
        foreach ($this->filesToDelete as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->filesToDelete = [];
    }

    public function testRouteFilesReceivePrefixFromBootstrapKeys(): void
    {
        $rootFile = $this->createRouteFile("<?php\nRoute::get('/health', 'health');\n");
        $apiFile = $this->createRouteFile("<?php\nRoute::get('/v1/test', 'api');\n");
        $adminFile = $this->createRouteFile("<?php\nRoute::get('/panel', 'admin');\n");

        $application = new Web(new class($rootFile, $apiFile, $adminFile) implements Bootstrap {
            public function __construct(
                private readonly string $rootFile,
                private readonly string $apiFile,
                private readonly string $adminFile,
            ) {
            }

            public function boot(Application $application): void
            {
            }

            public function getServiceProviders(): array
            {
                return [];
            }

            public function routes(): array
            {
                return [
                    '/' => $this->rootFile,
                    'api' => $this->apiFile,
                    '/admin/' => $this->adminFile,
                ];
            }
        });

        $paths = array_map(
            static fn ($route): string => $route->path,
            $application->getRouteCollection()->all()
        );

        self::assertSame(['/health', '/api/v1/test', '/admin/panel'], $paths);

        restore_error_handler();
        restore_exception_handler();
    }

    private function createRouteFile(string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), 'jotup-routes-');
        if ($file === false) {
            self::fail('Unable to create temporary route file.');
        }

        file_put_contents($file, $content);
        $this->filesToDelete[] = $file;

        return $file;
    }
}
