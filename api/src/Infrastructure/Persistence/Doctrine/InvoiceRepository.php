<?php

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
final class InvoiceRepository extends ServiceEntityRepository implements InvoiceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function save(Invoice $invoice): void
    {
        $em = $this->getEntityManager();
        $em->persist($invoice);
        $em->flush();
    }

    public function remove(Invoice $invoice): void
    {
        $em = $this->getEntityManager();
        $em->remove($invoice);
        $em->flush();
    }

    public function findOneById(Uuid $id): ?Invoice
    {
        return $this->createQueryBuilder('invoice')
            ->andWhere('invoice.id = :id')
            ->setParameter('id', $id, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
