<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Jotup\Database\Db;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Db\Migration\Service\MigrationService;

#[AsCommand('app:db:smoke', 'Checks database connectivity and pending migrations.')]
final class DbSmokeCommand extends Command
{
    public function __construct(
        private readonly Db $db,
        private readonly MigrationService $migrationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $driver = $this->db->connection()->getDriverName();
            $result = $this->db->command('SELECT 1')->queryScalar();
            $newMigrations = $this->migrationService->getNewMigrations();
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        $io->success('Database connection is healthy.');
        $io->listing([
            'Driver: ' . $driver,
            'SELECT 1 result: ' . (string) $result,
            'Pending migrations: ' . count($newMigrations),
        ]);

        return Command::SUCCESS;
    }
}
