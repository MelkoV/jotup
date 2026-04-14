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
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Blocking pop timeout in seconds.', '5')
            ->addOption('retry-delay', null, InputOption::VALUE_REQUIRED, 'Delay before retry after a transient queue error, in seconds.', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $once = (bool) $input->getOption('once');
        $timeout = max(1, (int) $input->getOption('timeout'));
        $retryDelay = max(1, (int) $input->getOption('retry-delay'));

        do {
            try {
                $message = $this->queue->pop($timeout);
            } catch (Throwable $exception) {
                if ($once) {
                    $io->error($exception->getMessage());

                    return Command::FAILURE;
                }

                $io->warning(sprintf(
                    'Queue read failed: %s. Retrying in %d second(s).',
                    $exception->getMessage(),
                    $retryDelay,
                ));
                sleep($retryDelay);

                continue;
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
