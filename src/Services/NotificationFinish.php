<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Repository\ContractsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationFinish
{
    public function __construct(
        public readonly ContractsRepository $contractsRepository,
        public readonly HttpClientInterface $httpClient,
        public readonly LoggerInterface     $logger
    )
    {

    }

    public function execute(): void
    {
        $items = $this->contractsRepository->findBy([
            'finish' => true,
            'notified' => false
        ]);

        foreach ($items as $item) {
            $this->notify($item);
        }

    }

    private function notify(Contracts $contracts): void
    {
        $items = $contracts->getSignatures()->toArray();
        $links = [];
        foreach ($items as $item) {
            if (empty($item->getLink())) {
                return;
            }
            $links[] = $item->getLink();
        }

        $this->sendToServer($links, $contracts);
    }

    private function sendToServer(array $links, Contracts $contracts):void
    {
        $url = $_ENV['APPSUPORTE_WEBHOOK'];
        $response = $this->httpClient->request('POST', $url, ['json' => [
            'cpf' => $contracts->getCpf(),
            'accessKey' => $contracts->getAccessKey(),
            'links' => $links
        ]]);

        try {
            $response->getContent();
            $contracts->setNotified(true);
            $this->contractsRepository->save($contracts);
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), [
                'cause' => $exception
            ]);
        }
    }

}