<?php

namespace App\Controller;

use App\DTO\Contract\V1\CreateContractDto;
use App\Entity\Contracts;
use App\Services\CreateContractService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class ContractApiController extends AbstractController
{
    #[Route('/api/contract/add', name: 'app_contract_create_api', methods: ['POST'])]
    public function index(#[MapRequestPayload] CreateContractDto $contractDto, CreateContractService $contractService, Request $request ): JsonResponse
    {
        $token = $request->query->get('token', null);
        if($token != $_ENV['API_TOKEN'] || $token==null){
            return  $this->json([],403);
        }

        return $this->json($contractService->create($contractDto));
    }

    #[Route('/api/contract/{contract}', name: 'app_contract_load_api')]
    public function load(Contracts $contract, Request $request ): JsonResponse
    {
        $token = $request->query->get('token', null);
        if($token != $_ENV['API_TOKEN'] || $token==null){
            return  $this->json([],403);
        }

        return $this->json($contract);
    }
}
