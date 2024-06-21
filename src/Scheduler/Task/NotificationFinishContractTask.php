<?php

namespace App\Scheduler\Task;

use App\Services\NotificationFinish;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 800)]
readonly class NotificationFinishContractTask
{
    public function __construct(
        private NotificationFinish $finish
    )
    {
    }

    public function __invoke(): void
    {
        $this->finish->execute();
    }
}