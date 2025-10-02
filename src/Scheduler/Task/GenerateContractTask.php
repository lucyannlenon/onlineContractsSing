<?php

namespace App\Scheduler\Task;


use App\Services\GeneratePdfContract;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 60)]
readonly class GenerateContractTask
{

    public function __construct(
        private GeneratePdfContract $pdfContract
    )
    {
    }

    public function __invoke(): void
    {
        $this->pdfContract->execute();
    }
}