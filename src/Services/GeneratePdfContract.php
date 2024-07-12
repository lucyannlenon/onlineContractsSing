<?php

namespace App\Services;

use App\Entity\ContractSignature;
use App\Repository\ContractSignatureRepository;
use Knp\Snappy\Pdf;
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
        private readonly ContractSignatureRepository $repository,
        private readonly Environment                 $environment,
        private readonly Pdf                         $pdf,
        private readonly FileGateway                 $gateway
    )
    {

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
        $payload = $item->getContract()->getPayload();
        $payload['signature'] = $item->getSignature();
        $payload['signature_date'] = $item->getCreatedAt()?->format('d/m/Y H:i:s');
        $html = $this->environment->render($this->templates[$item->md5Name()], $payload);

        $this->pdf->generateFromHtml(
            $html,
            $fileName,
            [

            ],
            true
        );


        dd($fileName);
        $this->uploadToServer($fileName, $item);
        unlink($fileName);
    }
}