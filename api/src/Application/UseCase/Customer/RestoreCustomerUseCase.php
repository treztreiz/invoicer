<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer;

use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\Service\Trait\CustomerRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Customer\Customer;

final class RestoreCustomerUseCase extends AbstractUseCase
{
    use CustomerRepositoryAwareTrait;

    public function handle(string $customerId): CustomerOutput
    {
        $customer = $this->findOneById($this->customerRepository, $customerId, Customer::class);

        $customer->unarchive();
        $this->customerRepository->save($customer);

        return $this->map($customer, CustomerOutput::class);
    }
}
