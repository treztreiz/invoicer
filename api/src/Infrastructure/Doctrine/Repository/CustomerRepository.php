<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository implements CustomerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function save(Customer $customer): void
    {
        $em = $this->getEntityManager();
        $em->persist($customer);
        $em->flush();
    }

    public function remove(Customer $customer): void
    {
        $em = $this->getEntityManager();
        $em->remove($customer);
        $em->flush();
    }

    public function findOneById(Uuid $id): ?Customer
    {
        return $this->createQueryBuilder('customer')
            ->andWhere('customer.id = :id')
            ->setParameter('id', $id, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Customer>
     */
    public function listActive(): array
    {
        return $this->createQueryBuilder('customer')
            ->andWhere('customer.isArchived = :archived')
            ->setParameter('archived', false)
            ->orderBy('customer.name.lastName', 'ASC')
            ->addOrderBy('customer.name.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
