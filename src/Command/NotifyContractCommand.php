<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ContractsRepository;
use App\Services\NotificationContractServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:notify:contract', description: 'show contracts by accept key')]
class NotifyContractCommand extends Command
{
    public function __construct(
        private readonly ContractsRepository        $contractsRepository,
        private readonly NotificationContractServer $notificationContractServer,
        ?string                                     $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('accessKey', InputArgument::REQUIRED, 'Access key for the contract')
            ->addArgument('cpf', InputArgument::REQUIRED, 'CPF number');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $items = $this->contractsRepository->findBy([
            'accessKey' => $input->getArgument('accessKey'),
            'cpf' => $input->getArgument('cpf')
        ]);

        foreach ($items as $item) {
            $this->notificationContractServer->notify($item);
        }

        return Command::SUCCESS;
    }
}
