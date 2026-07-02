<?php

namespace App\Services;

use App\Entity\ContractSignature;
use App\Enum\ContractTypeEnum;
use App\Infrastructure\Pdf\ChromiumPdf;
use App\Repository\ContractSignatureRepository;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class GeneratePdfContract
{
    private array $templates = [
        "c4da6e49819f469ff8c3d7b3b6f7318a" => 'main/accept-term.html.twig',
        "cee93e6616c8e23f26f84b73e87ce2d6" => 'main/granting-benefits.html.twig',
    ];

    public function __construct(
        private readonly string                      $tempDirectory,
        private readonly string                      $publicDir,
        private readonly ContractSignatureRepository $repository,
        private readonly Environment                 $environment,
        private readonly ChromiumPdf                 $pdf,
        private readonly FileGateway                 $gateway,
        private readonly SigningFlowService          $signingFlowService
    )
    {

    }

    /**
     * The PDF is rendered by Chromium from a file:// temp file, so root-relative
     * asset paths (/img/...) resolve against the filesystem root and break.
     * Embedding the brand logo as a data URI makes it render reliably.
     */
    private function brandLogoDataUri(): ?string
    {
        $path = $this->publicDir . '/img/izi-logo.png';
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($contents);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function execute(): void
    {
        $items = $this->repository->findBy(['link' => null]);

        foreach ($items as $item) {
            $this->processItem($item);
        }
    }

    /**
     * Generates the still-pending PDFs (link === null) for a single contract.
     * Used by the event-driven fast path; safe to call repeatedly (idempotent).
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function executeForContract(\App\Entity\Contracts $contract): void
    {
        foreach ($contract->getSignatures() as $item) {
            if ($item->getLink() === null) {
                $this->processItem($item);
            }
        }
    }

    /**
     * @param ContractSignature $item
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function processItem(ContractSignature $item): void
    {
        $this->generatePdf($item);
        $this->repository->save($item);
    }

    private function uploadToServer(string $fileName, ContractSignature $item): void
    {
        $id = md5($fileName . $item->getSignature() ?? uniqid());
        $this->gateway->uploadPath($fileName, $id);
        $link = $this->gateway->getUrlView($id);
        $item->setLink($link);
    }

    private function getDir(): string
    {
        $dirName = $this->tempDirectory . "/tmp";
        if (!is_dir($dirName)) {
            mkdir($dirName);
        }
        return $dirName;
    }

    /**
     * @param ContractSignature $item
     * @return void
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function generatePdf(ContractSignature $item): void
    {

        $fileName = $this->getDir() . "/{$item->getId()}.pdf";
        $brandLogo = $this->brandLogoDataUri();
        if ($item->getContract()->getContractType() === ContractTypeEnum::TEMPLATE) {
            $payload = [
                'contract' => $item->getContract(),
                'document_title' => $this->signingFlowService->templateTitle($item->getContract()),
                'signature_progress' => $this->signingFlowService->progress($item->getContract()),
                'signature' => $item->getSignature(),
                'signature_date' => $item->getCreatedAt()?->format('d/m/Y H:i:s'),
                'signature_evidence_base64' => $item->getEvidenceBase64(),
                'is_pdf' => true,
                'brand_logo_base64' => $brandLogo,
            ];
            $html = $this->environment->render('main/accept-contract.html.twig', $payload);
        } else {
            $payload = $item->getContract()->getPayload();
            $payload['document_title'] = $item->getName() === SigningFlowService::DOCUMENT_BENEFITS
                ? 'Termo de concessão de benefícios'
                : 'Termo de aceite';
            $payload['signature_progress'] = $this->signingFlowService->progress($item->getContract());
            $payload['signature'] = $item->getSignature();
            $payload['signature_date'] = $item->getCreatedAt()?->format('d/m/Y H:i:s');
            $payload['signature_evidence_base64'] = $item->getEvidenceBase64();
            $payload['is_pdf'] = true;
            $payload['brand_logo_base64'] = $brandLogo;
            $html = $this->environment->render($this->templates[$item->md5Name()], $payload);
        }
        $this->pdf->generateFromHtml($html, $fileName);


        $this->uploadToServer($fileName, $item);
        unlink($fileName);
    }
}
