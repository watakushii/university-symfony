<?php

namespace App\Command;

use App\Message\NotifyStudentsMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Test command to dispatch NotifyStudentsMessage.
 * 
 * Usage: php bin/console app:test-notify-students
 */
#[AsCommand(
    name: 'app:test-notify-students',
    description: 'Test command to dispatch NotifyStudentsMessage'
)]
class TestNotifyStudentsCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Dispatching NotifyStudentsMessage');

        try {
            $message = new NotifyStudentsMessage();
            $this->messageBus->dispatch($message);

            $io->success('Message dispatched successfully!');
            $io->note('To process the message, run: php bin/console messenger:consume async -vv');
            $io->note('Check var/log/dev.log for notification details after consuming the message.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error dispatching message: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

