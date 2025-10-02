<?php

declare(strict_types=1);

namespace App\Command;

use App\Enum\NotificationServerEnum;
use App\Repository\ContractsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'app:show_contract:notifyData', description: 'show contracts by accept key')]
class ShowContractNotifyDataCommand extends Command
{
    public function __construct(
        private readonly ContractsRepository $contractsRepository,
        private readonly SerializerInterface $serializer,

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


        foreach ($items as $contracts) {
            $itemsLinke = $contracts->getSignatures()->toArray();
            $links = [];
            foreach ($itemsLinke as $item) {
                if (empty($item->getLink())) {
                    continue;
                }
                $links[] = $item->getLink();
            }
            $post = [
                'cpf' => $contracts->getCpf(),
                'accessKey' => $contracts->getAccessKey(),
                'links' => $links,
                'action' => NotificationServerEnum::FINISH->name,
                'contractType' => $contracts->getContractType()
            ];

            $output->writeln($this->serializer->serialize($post, 'json'));
        }

        return Command::SUCCESS;
    }
}
