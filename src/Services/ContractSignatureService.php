<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Entity\ContractSignature;
use App\Repository\ContractsRepository;
use Psr\Log\LoggerInterface;

readonly class ContractSignatureService
{
    public function __construct(
        private ContractsRepository $contractsRepository,
        private SignatureService    $signatureService,
        private LoggerInterface     $logger
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
        $this->logger->info('Processing accept term signature', [
            'contract_id' => $contracts->getId(),
            'name' => $name
        ]);
        $this->singItem($clientInfo, $content, $contracts, $name);
    }

    /**
     * @param array $clientInfo
     * @param string $content
     * @param Contracts $contracts
     * @return void
     * @throws \Exception
     */
    public function singContractTerm(array $clientInfo, string $content, Contracts $contracts): void
    {
        $name = "Assinatura de Contrato";
        $this->logger->info('Processing contract term signature', [
            'contract_id' => $contracts->getId(),
            'name' => $name
        ]);
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
        $this->logger->info('Processing benefits term signature', [
            'contract_id' => $contracts->getId(),
            'name' => $name
        ]);
        $this->singItem($clientInfo, $content, $contracts, $name);
    }

    private function addToContract(Contracts $contracts, string $sing, string $name): void
    {
        if (!$singContract = $contracts->getSignaturesByName($name)) {
            $singContract = new ContractSignature();
            $singContract->setName($name);
            $contracts->addSignature($singContract);
            $this->logger->info('Created new signature for contract', [
                'contract_id' => $contracts->getId(),
                'name' => $name
            ]);
        }
        $singContract->setSignature($sing);

        $this->contractsRepository->save($contracts);
        $this->logger->info('Updated contract signature', [
            'contract_id' => $contracts->getId(),
            'name' => $name
        ]);
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