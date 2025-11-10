<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

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
            ->leftJoin('invoice.recurrence', 'recurrence')
            ->addSelect('recurrence')
            ->leftJoin('invoice.installmentPlan', 'installmentPlan')
            ->addSelect('installmentPlan')
            ->leftJoin('installmentPlan.installments', 'installments')
            ->addSelect('installments')
            ->andWhere('invoice.id = :id')
            ->setParameter('id', $id, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function list(): array
    {
        return $this->createQueryBuilder('invoice')
            ->orderBy('invoice.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function refresh(Invoice $invoice): Invoice
    {
        $this->getEntityManager()->refresh($invoice);

        return $invoice;
    }
}
