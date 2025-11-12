<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Stub;

use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use Symfony\Component\Uid\Uuid;

final class CustomerRepositoryStub implements CustomerRepositoryInterface
{
    public function __construct(private ?Customer $customer = null)
    {
    }

    public function save(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function remove(Customer $customer): void
    {
    }

    public function findOneById(Uuid $id): ?Customer
    {
        return $this->customer;
    }

    public function listActive(): array
    {
        return $this->customer ? [$this->customer] : [];
    }
}
