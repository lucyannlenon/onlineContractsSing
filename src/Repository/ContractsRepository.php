<?php

namespace App\Repository;

use App\Entity\Contracts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contracts>
 */
class ContractsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contracts::class);
    }

    //    /**
    //     * @return Contracts[] Returns an array of Contracts objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Contracts
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Próximo contrato pendente do mesmo cliente/lote, para encadear a assinatura.
     *
     * Escopo: mesmo CPF + mesmo birthday + não finalizado + criado na mesma janela
     * do contrato de referência. Evita arrastar contratos antigos/abandonados do CPF.
     */
    public function findNextPendingForBatch(Contracts $reference): ?Contracts
    {
        return $this->createBatchQueryBuilder($reference)
            ->andWhere('c.finish = :finish')
            ->andWhere('c.id != :id')
            ->setParameter('finish', false)
            ->setParameter('id', $reference->getId())
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Contracts[]
     */
    public function findBatchForContract(Contracts $reference): array
    {
        return $this->createBatchQueryBuilder($reference)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function createBatchQueryBuilder(Contracts $reference): \Doctrine\ORM\QueryBuilder
    {
        $window = 600; // segundos (±10 min)
        $createdAt = $reference->getCreatedAt();

        return $this->createQueryBuilder('c')
            ->andWhere('c.cpf = :cpf')
            ->andWhere('c.birthday = :birthday')
            ->andWhere('c.createdAt BETWEEN :from AND :to')
            ->setParameter('cpf', $reference->getCpf())
            ->setParameter('birthday', $reference->getBirthday())
            ->setParameter('from', $createdAt->modify("-{$window} seconds"))
            ->setParameter('to', $createdAt->modify("+{$window} seconds"));
    }

    public function save(Contracts $contracts):void
    {
        $this->getEntityManager()->persist($contracts);
        $this->getEntityManager()->flush();
    }
}
