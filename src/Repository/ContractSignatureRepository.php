<?php

namespace App\Repository;

use App\Entity\Contracts;
use App\Entity\ContractSignature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContractSignature>
 */
class ContractSignatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractSignature::class);
    }

    public function save(ContractSignature $contracts):void
    {
        $this->getEntityManager()->persist($contracts);
        $this->getEntityManager()->flush();
    }
}
