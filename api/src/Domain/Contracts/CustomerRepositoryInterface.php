<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Entity\Customer\Customer;
use Symfony\Component\Uid\Uuid;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function remove(Customer $customer): void;

    public function findOneById(Uuid $id): ?Customer;
}
