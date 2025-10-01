<?php

namespace App\Controller;

use App\DTO\AuthMainDto;
use App\Entity\Contracts;
use App\Enum\ContractTypeEnum;
use App\Repository\ContractsRepository;
use App\Services\ContractSignatureService;
use App\Services\CreateContractService;
use App\Services\LocalToken;
use App\Services\SignatureService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    public function __construct(
        public LocalToken $localToken
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
    public function checkCredentials(#[MapRequestPayload] AuthMainDto $authMainDTO, ContractsRepository $repository): Response
    {
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
        return $this->redirect("/accept-contract/{$item->getId()}");
    }

    #[Route('/accept-contract/{contract}', name: 'app_accept_contract')]
    public function acceptContractTemplate(Contracts $contract, Request $request, ContractSignatureService $signatureService): Response
    {
        if ($contract->getContractType() == ContractTypeEnum::DEFAULT || $contract->getContractType() == null) {
            return $this->redirect('/accept-term/' . $contract->getId());
        }

        $acceptKey = $request->get('accept-key', false);
        $payload = [
            'contract' => $contract,
            'enable_btn' => !$acceptKey
        ];
        $response = $this->render('main/accept-contract.html.twig', $payload);

        if ($acceptKey) {
            $clientInfo = [
                'ip_address' => $request->getClientIps(),
                'user_agent' => $request->getUserInfo(),
                'timestamp' => time()
            ];

            $signatureService->singAcceptTerm($clientInfo, $response->getContent(), $contract);
            return $this->redirect('/finish/' . $contract->getId());
        }
        return $response;
    }

    #[Route('/accept-term/{contract}')]
    public function acceptTerm(Contracts $contract, Request $request, ContractSignatureService $signatureService): Response
    {
        $acceptKey = $request->get('accept-key', false);
        $payload = $contract->getPayload();
        $payload['enable_btn'] = !$acceptKey;
        $response = $this->render('main/accept-term.html.twig', $payload);

        if ($acceptKey) {
            $clientInfo = [
                'ip_address' => $request->getClientIps(),
                'user_agent' => $request->getUserInfo(),
                'timestamp' => time()
            ];

            $signatureService->singAcceptTerm($clientInfo, $response->getContent(), $contract);
            return $this->redirect('/granting-benefits/' . $contract->getId());
        }
        return $response;
    }

    /**
     * @param Contracts $contract
     * @param Request $request
     * @param ContractSignatureService $signatureService
     * @return Response
     * @throws \Exception
     */
    #[Route('/granting-benefits/{contract}')]
    public function grantingBenefits(Contracts $contract, Request $request, ContractSignatureService $signatureService): Response
    {


        $payload = $contract->getPayload();
        $acceptKey = $request->get('accept-key', false);
        $payload['enable_btn'] = !$acceptKey;

        $response = $this->render('main/granting-benefits.html.twig', $payload);
        if ($acceptKey) {
            $clientInfo = [
                'ip_address' => $request->getClientIps(),
                'user_agent' => $request->getUserInfo(),
                'timestamp' => time()
            ];

            $signatureService->singBenefitsTerm($clientInfo, $response->getContent(), $contract);
            return $this->redirect('/finish/' . $contract->getId());
        }
        return $response;
    }

    #[Route('/finish/{contract}')]
    public function saveAll(Contracts $contract, CreateContractService $contractService): Response
    {
        $contractService->finishContract($contract);

        return $this->render('main/success.html.twig', [
        ]);
    }
}
