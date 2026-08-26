<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Entity\ContractSignature;
use App\Repository\ContractsRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
        $name = SigningFlowService::DOCUMENT_ACCEPT_TERM;
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
        $name = SigningFlowService::DOCUMENT_CONTRACT;
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
        $name = SigningFlowService::DOCUMENT_BENEFITS;
        $this->logger->info('Processing benefits term signature', [
            'contract_id' => $contracts->getId(),
            'name' => $name
        ]);
        $this->singItem($clientInfo, $content, $contracts, $name);
    }

    private function addToContract(Contracts $contracts, string $sing, string $name, array $evidence): void
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
        $singContract->setEvidence($evidence);

        try {
            $this->contractsRepository->save($contracts);
        } catch (UniqueConstraintViolationException) {
            // Two concurrent requests (e.g. a double-click) raced to sign the
            // same document; the other one already persisted an equivalent
            // signature, so there is nothing left to do here.
            $this->logger->warning('Concurrent signature request for the same document, discarding duplicate', [
                'contract_id' => $contracts->getId(),
                'name' => $name
            ]);
            return;
        }

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
        $evidence = $this->createEvidence($clientInfo, $content, $contracts, $name);
        $data = [
            'evidence' => $evidence,
            'content' => $content,
        ];

        $sing = $this->signatureService->sing($data);
        $this->addToContract($contracts, $sing, $name, $evidence);
    }

    private function createEvidence(array $clientInfo, string $content, Contracts $contracts, string $name): array
    {
        return [
            'version' => 1,
            'document' => [
                'name' => $name,
                'sha256' => hash('sha256', $content),
                'bytes' => strlen($content),
            ],
            'contract' => [
                'id' => $contracts->getId(),
                'type' => $contracts->getContractType()?->value,
                'cpf_sha256' => hash('sha256', (string) $contracts->getCpf()),
                'access_key_sha256' => hash('sha256', (string) $contracts->getAccessKey()),
                'created_at' => $contracts->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ],
            'client' => $clientInfo,
            'accepted_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }


}
