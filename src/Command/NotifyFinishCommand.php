<?php

namespace App\Command;

use App\Scheduler\Task\NotificationFinishContractTask;
use App\Services\GeneratePdfContract;
use App\Services\NotificationServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notify:finish',
    description: 'Add a short description for your command',
)]
class NotifyFinishCommand extends Command
{
    public function __construct(
        private readonly NotificationServer $notificationServer
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->notificationServer->execute();


        return Command::SUCCESS;
    }
}
