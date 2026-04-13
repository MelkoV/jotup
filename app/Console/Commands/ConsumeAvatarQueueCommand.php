<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AvatarQueueContract;
use App\Contracts\Services\AvatarUrlServiceContract;
use App\Exceptions\UserNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand('app:avatar:consume', 'Consumes avatar update jobs from Redis.')]
final class ConsumeAvatarQueueCommand extends Command
{
    public function __construct(
        private readonly AvatarQueueContract $queue,
        private readonly AvatarUrlServiceContract $avatarUrlService,
        private readonly UserRepositoryInterface $users,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('once', null, InputOption::VALUE_NONE, 'Process a single message or exit if queue is empty.')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Blocking pop timeout in seconds.', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $once = (bool) $input->getOption('once');
        $timeout = max(1, (int) $input->getOption('timeout'));

        do {
            try {
                $message = $this->queue->pop($timeout);
            } catch (Throwable $exception) {
                $io->error($exception->getMessage());

                return Command::FAILURE;
            }

            if ($message === null) {
                if ($once) {
                    $io->note('Avatar queue is empty.');

                    return Command::SUCCESS;
                }

                continue;
            }

            try {
                $user = $this->users->findById($message->user_id);
                $avatar = $this->avatarUrlService->getAvatarUrl($user);
                $this->users->updateAvatar($user, $avatar);

                $io->writeln(sprintf('Processed avatar update for user %s.', $user->id));
            } catch (UserNotFoundException) {
                $io->warning(sprintf('User %s was not found. Message skipped.', $message->user_id));
            } catch (Throwable $exception) {
                $io->error($exception->getMessage());

                return Command::FAILURE;
            }

            if ($once) {
                return Command::SUCCESS;
            }
        } while (true);
    }
}
