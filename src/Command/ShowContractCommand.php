<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ContractsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:show_contract', description: 'show contracts by accept key')]
class showContractCommand extends Command
{
    public function __construct(
        private readonly ContractsRepository $contractsRepository,

        ?string                              $name = null)
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

        dump($items);

        return Command::SUCCESS;
    }
}
