<?php

namespace App\EventListener;

use App\Entity\ContractSignature;
use App\Enum\NotificationServerEnum;
use App\Services\NotificationServer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::prePersist, method: 'onCreateSignature', entity: ContractSignature::class)]
class ContractSignatureListener
{


    public function __construct(
        private readonly LoggerInterface    $logger,
        private readonly NotificationServer $notificationServer
    )
    {
    }

    public function onCreateSignature(ContractSignature $contractSignature): void
    {
        try {

            $post = [
                'cpf' => $contractSignature->getContract()->getCpf(),
                'accessKey' => $contractSignature->getContract()->getAccessKey(),
                'action' => NotificationServerEnum::UPDATE_SIGNATURE->name,
                'message' => $contractSignature->getName()
            ];
            $this->notificationServer->sendToServer($post);

        } catch (\Exception|\Throwable $e) {

            $this->logger->critical("Can not update status contract in remote server because error: {$e->getMessage()}", [
                'cause' => $e
            ]);
        }
    }
}