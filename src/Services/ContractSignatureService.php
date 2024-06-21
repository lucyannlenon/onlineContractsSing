<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Entity\ContractSignature;
use App\Repository\ContractsRepository;

class ContractSignatureService
{
    public function __construct(
        private readonly ContractsRepository $contractsRepository,
        private readonly SignatureService    $signatureService
    )
    {

    }

    /**
     * @param array $clientInfo
     * @param string $content
     * @param Contracts $contracts
     * @return void
     * @throws \Exception
     */
    public function singAcceptTerm(array $clientInfo, string $content, Contracts $contracts): void
    {
        $name = "TERMO ACEITE";
        $this->singItem($clientInfo, $content, $contracts, $name);
    }

    /**
     * @param array $clientInfo
     * @param string $content
     * @param Contracts $contracts
     * @return void
     * @throws \Exception
     */
    public function singBenefitsTerm(array $clientInfo, string $content, Contracts $contracts): void
    {
        $name = "TERMO CONCESSÃO DE BENEFÍCIO";
        $this->singItem($clientInfo, $content, $contracts, $name);
    }

    private function addToContract(Contracts $contracts, string $sing, string $name): void
    {
        if (!$singContract = $contracts->getSignaturesByName($name)) {
            $singContract = new ContractSignature();
            $singContract->setName($name);
            $contracts->addSignature($singContract);
        }
        $singContract->setSignature($sing);

        $this->contractsRepository->save($contracts);
    }

    /**
     * @param array $clientInfo
     * @param string $content
     * @param Contracts $contracts
     * @param string $name
     * @return void
     * @throws \Exception
     */
    public function singItem(array $clientInfo, string $content, Contracts $contracts, string $name): void
    {
        $data = [
            'clientInfo' => $clientInfo,
            'content' => $content
        ];

        $sing = $this->signatureService->sing($data);
        $this->addToContract($contracts, $sing, $name);
    }



}