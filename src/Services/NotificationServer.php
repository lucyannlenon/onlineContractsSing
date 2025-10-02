<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Enum\NotificationServerEnum;
use App\Repository\ContractsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class NotificationServer
{
    public function __construct(
        private ContractsRepository        $contractsRepository,
        private NotificationContractServer $notificationContractServer
    )
    {

    }

    /**
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function execute(): void
    {
        $items = $this->contractsRepository->findBy([
            'finish' => true,
            'notified' => false
        ]);

        foreach ($items as $item) {
            $this->notificationContractServer->notify($item);
        }

    }

}