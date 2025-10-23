<?php

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer;
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
        $this->getEntityManager()->persist($customer);
        $this->getEntityManager()->flush();
    }

    public function remove(Customer $customer): void
    {
        $this->getEntityManager()->remove($customer);
        $this->getEntityManager()->flush();
    }

    public function findOneById(Uuid $id): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('user', $id, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
