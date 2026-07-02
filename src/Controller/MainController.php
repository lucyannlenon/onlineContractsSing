<?php

namespace App\Controller;

use App\DTO\AuthMainDto;
use App\Entity\Contracts;
use App\Message\FinalizeContractNotification;
use App\Repository\ContractsRepository;
use App\Services\ContractSignatureService;
use App\Services\CreateContractService;
use App\Services\LocalToken;
use App\Services\SigningFlowService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    public function __construct(
        public LocalToken $localToken, 
        public LoggerInterface $logger,
    )
    {

    }

    #[Route('/', name: 'app_main', methods: ['GET'])]
    public function index(Request $request): Response
    {

        return $this->render('main/index.html.twig', [
            'error' => $request->query->get('message', null)
        ]);

    }

    #[Route('/', name: 'app_main_post', methods: ['POST'])]
    public function checkCredentials(
        #[MapRequestPayload] AuthMainDto $authMainDTO,
        ContractsRepository $repository,
        SigningFlowService $signingFlowService
    ): Response
    {
        $this->logger->info('Checking credentials', [
            'cpf' => $authMainDTO->getCpf(),
            'birthday' => $authMainDTO->birthday
        ]);

        $item = $repository->findOneBy([
            'cpf' => $authMainDTO->getCpf(),
            'accessKey' => $authMainDTO->key,
            'birthday' => $authMainDTO->birthday
        ]);

        if (!$item) {
            return $this->render('main/index.html.twig', [
                'error' => "Nenhum contrato entrado para os dados fornecidos"
            ]);
        }

        $nextUrl = $signingFlowService->nextPendingDocumentUrl($item);
        if ($nextUrl !== null) {
            return $this->redirect($nextUrl);
        }

        return $this->render('main/success.html.twig', [
            'signature_progress' => $signingFlowService->progress($item),
        ]);
    }

    #[Route('/accept-contract/{contract}', name: 'app_accept_contract')]
    public function acceptContractTemplate(
        Contracts $contract,
        Request $request,
        ContractSignatureService $signatureService,
        SigningFlowService $signingFlowService
    ): Response
    {
        $this->logger->info('Processing contract template acceptance', [
            'contract_id' => $contract->getId(),
            'contract_type' => $contract->getContractType()?->name
        ]);

        if (!$signingFlowService->isTemplateContract($contract)) {
            if ($contract->getSignaturesByName(SigningFlowService::DOCUMENT_ACCEPT_TERM)) {
                return $this->redirect('/granting-benefits/' . $contract->getId());
            }

            return $this->redirect('/accept-term/' . $contract->getId());
        }

        $acceptKey = $request->get('accept-key', false);
        $signature = $contract->getSignaturesByName(SigningFlowService::DOCUMENT_CONTRACT);
        $payload = [
            'contract' => $contract,
            'enable_btn' => !$acceptKey,
            'document_title' => $signingFlowService->templateTitle($contract),
            'signature_progress' => $signingFlowService->progress($contract),
            'signature' => $signature?->getSignature(),
            'signature_date' => $signature?->getCreatedAt()?->format('d/m/Y H:i:s'),
            'signature_evidence_base64' => $signature?->getEvidenceBase64(),
        ];
        $response = $this->render('main/accept-contract.html.twig', $payload);

        if ($acceptKey) {
            $signatureService->singContractTerm($this->clientInfo($request), $response->getContent(), $contract);
            return $this->redirect('/finish/' . $contract->getId());
        }
        return $response;
    }

    #[Route('/accept-term/{contract}')]
    public function acceptTerm(
        Contracts $contract,
        Request $request,
        ContractSignatureService $signatureService,
        SigningFlowService $signingFlowService
    ): Response
    {
        $this->logger->info('Processing term acceptance', [
            'contract_id' => $contract->getId()
        ]);

        $acceptKey = $request->get('accept-key', false);
        if (!$acceptKey && $contract->getSignaturesByName(SigningFlowService::DOCUMENT_ACCEPT_TERM)) {
            return $this->redirect('/granting-benefits/' . $contract->getId());
        }

        $signature = $contract->getSignaturesByName(SigningFlowService::DOCUMENT_ACCEPT_TERM);
        $payload = $contract->getPayload();
        $payload['enable_btn'] = !$acceptKey;
        $payload['document_title'] = 'Termo de aceite';
        $payload['signature_progress'] = $signingFlowService->progress($contract);
        $payload['signature'] = $signature?->getSignature();
        $payload['signature_date'] = $signature?->getCreatedAt()?->format('d/m/Y H:i:s');
        $payload['signature_evidence_base64'] = $signature?->getEvidenceBase64();
        $response = $this->render('main/accept-term.html.twig', $payload);

        if ($acceptKey) {
            $signatureService->singAcceptTerm($this->clientInfo($request), $response->getContent(), $contract);
            return $this->redirect('/granting-benefits/' . $contract->getId());
        }
        return $response;
    }

    /**
     * @param Contracts $contract
     * @param Request $request
     * @param ContractSignatureService $signatureService
     * @return Response
     * @throws Exception
     */
    #[Route('/granting-benefits/{contract}')]
    public function grantingBenefits(
        Contracts $contract,
        Request $request,
        ContractSignatureService $signatureService,
        SigningFlowService $signingFlowService
    ): Response
    {
        $this->logger->info('Processing benefits granting', [
            'contract_id' => $contract->getId()
        ]);


        $payload = $contract->getPayload();
        $acceptKey = $request->get('accept-key', false);
        $signature = $contract->getSignaturesByName(SigningFlowService::DOCUMENT_BENEFITS);
        $payload['enable_btn'] = !$acceptKey;
        $payload['document_title'] = 'Termo de concessão de benefícios';
        $payload['signature_progress'] = $signingFlowService->progress($contract);
        $payload['signature'] = $signature?->getSignature();
        $payload['signature_date'] = $signature?->getCreatedAt()?->format('d/m/Y H:i:s');
        $payload['signature_evidence_base64'] = $signature?->getEvidenceBase64();

        $response = $this->render('main/granting-benefits.html.twig', $payload);
        if ($acceptKey) {
            $signatureService->singBenefitsTerm($this->clientInfo($request), $response->getContent(), $contract);
            return $this->redirect('/finish/' . $contract->getId());
        }
        return $response;
    }

    #[Route('/finish/{contract}', name: 'app_finish')]
    public function saveAll(
        Contracts $contract,
        CreateContractService $contractService,
        SigningFlowService $signingFlowService,
        MessageBusInterface $bus
    ): Response
    {
        $this->logger->info('Finalizing contract', [
            'contract_id' => $contract->getId()
        ]);

        // marca o atual como finalizado (finish = true, notified = false)
        $contractService->finishContract($contract);

        // Dispara a geração+notificação por evento (caminho rápido) para ESTE
        // contrato — antes do encadeamento, senão só o último da cadeia recebe
        // notificação realtime e os demais esperam o fallback periódico.
        // Envolto em try/catch: se o broker estiver fora, o usuário não percebe
        // e os jobs periódicos concluem como fallback.
        try {
            $bus->dispatch(new FinalizeContractNotification($contract->getId()));
        } catch (\Throwable $exception) {
            $this->logger->error('Could not enqueue finalize notification', [
                'contract_id' => $contract->getId(),
                'error' => $exception->getMessage(),
            ]);
        }

        // encadeia: há outro documento pendente do mesmo lote/CPF?
        $nextUrl = $signingFlowService->nextPendingDocumentUrl($contract);
        if ($nextUrl !== null) {
            $this->logger->info('Chaining to next pending contract', [
                'from_contract_id' => $contract->getId(),
                'next_url' => $nextUrl,
            ]);
            return $this->redirect($nextUrl);
        }

        return $this->render('main/success.html.twig', [
            'signature_progress' => $signingFlowService->progress($contract),
        ]);
    }

    private function clientInfo(Request $request): array
    {
        return [
            'client_ip' => $request->getClientIp(),
            'client_ips' => $request->getClientIps(),
            'user_agent' => $request->headers->get('User-Agent'),
            'forwarded_for' => $request->headers->get('X-Forwarded-For'),
            'forwarded_proto' => $request->headers->get('X-Forwarded-Proto'),
            'accepted_at_unix' => time(),
        ];
    }
}
