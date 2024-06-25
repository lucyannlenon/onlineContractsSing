<?php

namespace App\Scheduler\Task;

use App\Services\NotificationServer;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 80)]
readonly class NotificationFinishContractTask
{
    public function __construct(
        private NotificationServer $notificationServer
    )
    {
    }

    public function __invoke(): void
    {
        $this->notificationServer->execute();
    }
}