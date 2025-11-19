<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer;

use App\Application\Dto\Customer\Input\CustomerInput;
use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\Service\Trait\CustomerRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Payload\Customer\CustomerPayload;

final class UpdateCustomerUseCase extends AbstractUseCase
{
    use CustomerRepositoryAwareTrait;

    public function handle(CustomerInput $input, string $customerId): CustomerOutput
    {
        $customer = $this->findOneById($this->customerRepository, $customerId, Customer::class);

        $payload = $this->map($input, CustomerPayload::class);
        $customer->applyPayload($payload);

        $this->customerRepository->save($customer);

        return $this->map($customer, CustomerOutput::class);
    }
}
