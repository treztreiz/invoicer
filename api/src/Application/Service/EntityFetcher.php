<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Exception\ResourceNotFoundException;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;

final readonly class EntityFetcher
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function customer(string $id): Customer
    {
        $customer = $this->customerRepository->findOneById(Uuid::fromString($id));

        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $id);
        }

        return $customer;
    }

    public function user(string $id): User
    {
        $user = $this->userRepository->findOneById(Uuid::fromString($id));

        if (null === $user) {
            throw new ResourceNotFoundException('User', $id);
        }

        return $user;
    }
}
