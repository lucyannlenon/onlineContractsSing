<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Repository\ContractsRepository;

readonly class CancelContractService
{
    public function __construct(
        private ContractsRepository $repository,
    ) {
    }

    /**
     * @throws \RuntimeException if the contract was already fully signed
     */
    public function cancel(Contracts $contract): void
    {
        if ($contract->isCanceled()) {
            return;
        }

        if ($contract->isFinish()) {
            throw new \RuntimeException(sprintf('Contract %d is already signed, cannot cancel', $contract->getId()));
        }

        $contract->cancel(new \DateTimeImmutable());
        $this->repository->save($contract);
    }
}
