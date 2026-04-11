<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:about', 'Shows basic information about the application.')]
final class AboutCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Jotup Console</info>');
        $output->writeln('Framework playground with PSR-7, PSR-15 and Yii DB integration.');

        return Command::SUCCESS;
    }
}
