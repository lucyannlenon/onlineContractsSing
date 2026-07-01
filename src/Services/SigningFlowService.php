<?php

namespace App\Services;

use App\Entity\Contracts;
use App\Enum\ContractTypeEnum;
use App\Repository\ContractsRepository;

readonly class SigningFlowService
{
    public const DOCUMENT_ACCEPT_TERM = 'TERMO ACEITE';
    public const DOCUMENT_CONTRACT = 'Assinatura de Contrato';
    public const DOCUMENT_BENEFITS = 'TERMO CONCESSÃO DE BENEFÍCIO';

    public function __construct(
        private ContractsRepository $contractsRepository
    ) {
    }

    public function nextPendingDocumentUrl(Contracts $reference): ?string
    {
        foreach ($this->contractsRepository->findBatchForContract($reference) as $contract) {
            if ($this->isTemplateContract($contract)) {
                if (!$contract->getSignaturesByName(self::DOCUMENT_CONTRACT) && !$contract->isFinish()) {
                    return '/accept-contract/' . $contract->getId();
                }

                continue;
            }

            if (!$contract->getSignaturesByName(self::DOCUMENT_ACCEPT_TERM)) {
                return '/accept-contract/' . $contract->getId();
            }

            if (!$contract->getSignaturesByName(self::DOCUMENT_BENEFITS) && !$contract->isFinish()) {
                return '/granting-benefits/' . $contract->getId();
            }
        }

        return null;
    }

    public function progress(Contracts $reference): array
    {
        $total = 0;
        $signed = 0;

        foreach ($this->contractsRepository->findBatchForContract($reference) as $contract) {
            if ($this->isTemplateContract($contract)) {
                $total++;
                if ($contract->getSignaturesByName(self::DOCUMENT_CONTRACT) || $contract->isFinish()) {
                    $signed++;
                }

                continue;
            }

            $total += 2;
            if ($contract->getSignaturesByName(self::DOCUMENT_ACCEPT_TERM)) {
                $signed++;
            }

            if ($contract->getSignaturesByName(self::DOCUMENT_BENEFITS) || $contract->isFinish()) {
                $signed++;
            }
        }

        return [
            'signed' => $signed,
            'total' => max($total, 1),
            'label' => sprintf('Assinado %d de %d', $signed, max($total, 1)),
        ];
    }

    public function templateTitle(Contracts $contract): string
    {
        $template = $contract->getTemplate();
        if (!$template) {
            return 'Documento atual';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $template);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return 'Documento atual';
        }

        $xpath = new \DOMXPath($document);
        foreach (['//h1', '//h2', '//h3', '//title'] as $query) {
            $nodes = $xpath->query($query);
            if ($nodes === false || $nodes->length === 0) {
                continue;
            }

            $title = trim(preg_replace('/\s+/', ' ', $nodes->item(0)?->textContent ?? ''));
            if ($title !== '') {
                return $title;
            }
        }

        return 'Documento atual';
    }

    public function isTemplateContract(Contracts $contract): bool
    {
        return $contract->getContractType() !== ContractTypeEnum::DEFAULT && $contract->getContractType() !== null;
    }
}
