<?php

namespace App\Command;

use App\Services\SignatureService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-keys',
    description: 'Add a short description for your command',
)]
class GenerateKeysCommand extends Command
{
    public function __construct(
        public readonly SignatureService $service
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
        $this->service->generateKeys();

        $io->success('success on generate keys');

        return Command::SUCCESS;
    }
}
