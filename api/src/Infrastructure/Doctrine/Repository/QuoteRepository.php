<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Entity\Document\Quote;
use App\Domain\Filter\QuoteFilterCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Quote>
 */
final class QuoteRepository extends ServiceEntityRepository implements QuoteRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function save(Quote $quote): void
    {
        $em = $this->getEntityManager();
        $em->persist($quote);
        $em->flush();
    }

    public function remove(Quote $quote): void
    {
        $em = $this->getEntityManager();
        $em->remove($quote);
        $em->flush();
    }

    public function findOneById(Uuid $id): ?Quote
    {
        return $this->createQueryBuilder('quote')
            ->andWhere('quote.id = :id')
            ->setParameter('id', $id, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Quote>
     *
     * @throws QueryException
     */
    public function list(QuoteFilterCollection $filters): array
    {
        return $this->createQueryBuilder('quote')
            ->addCriteria(self::statusFilterCriteria($filters))
            ->orderBy('quote.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private static function statusFilterCriteria(QuoteFilterCollection $filters): Criteria
    {
        $criteria = Criteria::create();

        return empty($filters->statuses)
            ? $criteria
            : $criteria->andWhere(Criteria::expr()->in('status', $filters->statuses));
    }
}
