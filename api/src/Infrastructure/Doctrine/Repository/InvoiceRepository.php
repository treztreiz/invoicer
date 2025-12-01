<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Contracts\Repository\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
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

    public function findRecurrenceSeeds(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('invoice')
            ->innerJoin('invoice.recurrence', 'recurrence')
            ->addSelect('recurrence')
            ->andWhere('recurrence.nextRunAt IS NOT NULL')
            ->andWhere('recurrence.nextRunAt <= :date')
            ->setParameter('date', $date, Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }
}
