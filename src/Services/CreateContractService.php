<?php

namespace App\Services;

use App\DTO\Contract\V1\CreateContractDto;
use App\Entity\Contracts;
use App\Repository\ContractsRepository;

readonly class CreateContractService
{
    public function __construct(
        private ContractsRepository $repository
    )
    {

    }

    public function create(CreateContractDto $contractDto): Contracts
    {
        $code = $this->getCode($contractDto->cpf);
        $contracts = new Contracts();
        $contracts->setCpf($contractDto->cpf);
        $contracts->setBirthday($contractDto->birthday);
        $contracts->setAccessKey($code);
        $contracts->setPayload((array)$contractDto->payload);
        $this->repository->save($contracts);
        return $contracts;
    }

    private function getCode(string $cpf): string
    {

        $code = rand(10000, 99999);

        $item = $this->repository->findOneBy([
            'cpf' => $cpf,
            'accessKey' => $code
        ]);

        if (!$item) {
            return $code;
        }

        return $this->getCode($cpf);
    }
}