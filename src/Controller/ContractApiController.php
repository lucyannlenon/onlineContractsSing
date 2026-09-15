<?php

namespace App\Controller;

use App\DTO\Contract\V1\CreateContractDto;
use App\DTO\Contract\V2\CreateContractDto as CreateContractDtoV2;
use App\Entity\Contracts;
use App\Repository\ContractsRepository;
use App\Services\CancelContractService;
use App\Services\CreateContractByTemplateService;
use App\Services\CreateContractService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class ContractApiController extends AbstractController
{
    #[Route('/api/contract/add', name: 'app_contract_create_api', methods: ['POST'])]
    public function index(#[MapRequestPayload] CreateContractDto $contractDto, CreateContractService $contractService, Request $request): JsonResponse
    {
        $token = $request->query->get('token', null);
        if ($token != $_ENV['API_TOKEN'] || $token == null) {
            return $this->json([], 403);
        }

        return $this->json($contractService->create($contractDto));
    }

    #[Route('/api/contract/add-template', name: 'app_contract_create_api_v2', methods: ['POST'])]
    public function addV2(#[MapRequestPayload] CreateContractDtoV2 $contractDto, CreateContractByTemplateService $contractService, Request $request): JsonResponse
    {
        $token = $request->query->get('token', null);
        if ($token != $_ENV['API_TOKEN'] || $token == null) {
            return $this->json([], 403);
        }

        return $this->json($contractService->create($contractDto));
    }

    #[Route('/api/contract/{contract}', name: 'app_contract_load_api')]
    public function load(Contracts $contract, Request $request): JsonResponse
    {
        $token = $request->query->get('token', null);
        if ($token != $_ENV['API_TOKEN'] || $token == null) {
            return $this->json([], 403);
        }

        return $this->json($contract);
    }

    /**
     * Pulls a contract out of the client's pending signature batch — used by the
     * source system when it replaces/cancels whatever created this contract
     * before the client got to sign it (e.g. two combo subscriptions created for
     * the same account, only the latest one should still ask for a signature).
     */
    #[Route('/api/contract/{accessKey}/cancel', name: 'app_contract_cancel_api', methods: ['POST'])]
    public function cancel(string $accessKey, Request $request, ContractsRepository $repository, CancelContractService $cancelContractService, LoggerInterface $logger): JsonResponse
    {
        $token = $request->query->get('token', null);
        if ($token != $_ENV['API_TOKEN'] || $token == null) {
            return $this->json([], 403);
        }

        // accessKey alone isn't globally unique — CreateContractByTemplateService
        // only guarantees it's unique within the same cpf (see getCode()), so a
        // lookup by accessKey alone could hit a different customer's contract.
        $cpf = $request->query->get('cpf', null);
        if ($cpf === null) {
            return $this->json(['error' => 'cpf is required'], 422);
        }

        $contract = $repository->findOneBy(['accessKey' => $accessKey, 'cpf' => $cpf]);
        if (!$contract) {
            return $this->json(['error' => 'Contract not found'], 404);
        }

        try {
            $cancelContractService->cancel($contract);
        } catch (\RuntimeException $exception) {
            $logger->warning('[ContractApiController] Refused to cancel an already-signed contract', [
                'contract_id' => $contract->getId(),
                'access_key' => $accessKey,
            ]);

            return $this->json(['error' => $exception->getMessage()], 409);
        }

        $logger->info('[ContractApiController] Contract canceled', [
            'contract_id' => $contract->getId(),
            'access_key' => $accessKey,
        ]);

        return $this->json($contract);
    }
}
