<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Enum\NotificationServerEnum;
use App\Repository\ContractsRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NotificationServer
{
    public function __construct(
        private readonly ContractsRepository $contractsRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger
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
            $this->notify($item);
        }

    }

    /**
     * @param Contracts $contracts
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
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
        $post = [
            'cpf' => $contracts->getCpf(),
            'accessKey' => $contracts->getAccessKey(),
            'links' => $links,
            'action' => NotificationServerEnum::FINISH->name
        ];

        try {
            $this->sendToServer($post);
            $contracts->setNotified(true);
            $this->contractsRepository->save($contracts);
            dd($contracts);
        } catch (\Exception $exception) {
            dd($exception);
            $this->logger->critical($exception->getMessage(), [
                'cause' => $exception
            ]);
        }
    }

    /**
     * @param array $links
     * @param Contracts $contracts
     * @return void
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function sendToServer(array $post): void
    {


        $url = $_ENV['APPSUPORTE_WEBHOOK'];

        $response = $this->httpClient->request('POST', $url, [
            'query' => [
                'token' => $_ENV['API_TOKEN']
            ],
            'json' => $post
        ]);

        $response->getContent();

    }

}