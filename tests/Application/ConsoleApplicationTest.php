<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Console\Bootstrap;
use App\Console\Commands\AboutCommand;
use App\Console\Commands\ConsumeAvatarQueueCommand;
use App\Console\Commands\DbSmokeCommand;
use App\Contracts\Repositories\ListRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Repositories\ListRepository;
use App\Repositories\UserRepository;
use Jotup\Application\Console;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Yiisoft\Db\Migration\Command\CreateCommand;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\HistoryCommand;
use Yiisoft\Db\Migration\Command\NewCommand;
use Yiisoft\Db\Migration\Command\RedoCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;

final class ConsoleApplicationTest extends TestCase
{
    public function testBootstrapRegistersConsoleCommands(): void
    {
        $application = new Console(new Bootstrap());
        $reflection = new ReflectionClass($application);
        $property = $reflection->getProperty('commands');

        /** @var list<Command> $commands */
        $commands = $property->getValue($application);
        $commandClasses = array_map(static fn (Command $command): string => $command::class, $commands);

        $this->assertContains(AboutCommand::class, $commandClasses);
        $this->assertContains(DbSmokeCommand::class, $commandClasses);
        $this->assertContains(ConsumeAvatarQueueCommand::class, $commandClasses);
        $this->assertContains(CreateCommand::class, $commandClasses);
        $this->assertContains(DownCommand::class, $commandClasses);
        $this->assertContains(HistoryCommand::class, $commandClasses);
        $this->assertContains(NewCommand::class, $commandClasses);
        $this->assertContains(RedoCommand::class, $commandClasses);
        $this->assertContains(UpdateCommand::class, $commandClasses);

        restore_error_handler();
        restore_exception_handler();
    }

    public function testRepositoryContractResolvesToRepositoryImplementation(): void
    {
        $application = new Console(new Bootstrap());
        $repository = $application->getContainer()->get(UserRepositoryInterface::class);
        $listRepository = $application->getContainer()->get(ListRepositoryInterface::class);

        $this->assertInstanceOf(UserRepository::class, $repository);
        $this->assertInstanceOf(ListRepository::class, $listRepository);

        restore_error_handler();
        restore_exception_handler();
    }
}
