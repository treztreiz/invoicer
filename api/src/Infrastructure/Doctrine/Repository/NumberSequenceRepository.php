<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Contracts\NumberSequenceRepositoryInterface;
use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NumberSequence>
 */
final class NumberSequenceRepository extends ServiceEntityRepository implements NumberSequenceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NumberSequence::class);
    }

    public function save(NumberSequence $sequence): void
    {
        $em = $this->getEntityManager();
        $em->persist($sequence);
        $em->flush();
    }

    public function findOneByTypeAndYear(DocumentType $documentType, int $year): ?NumberSequence
    {
        return $this->createQueryBuilder('sequence')
            ->andWhere('sequence.documentType = :type')
            ->andWhere('sequence.year = :year')
            ->setParameter('type', $documentType)
            ->setParameter('year', $year)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
