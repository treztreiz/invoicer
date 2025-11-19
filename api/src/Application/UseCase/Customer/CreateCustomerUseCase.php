<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer;

use App\Application\Dto\Customer\Input\CustomerInput;
use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\Service\Trait\CustomerRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Payload\Customer\CustomerPayload;

final class CreateCustomerUseCase extends AbstractUseCase
{
    use CustomerRepositoryAwareTrait;

    public function handle(CustomerInput $input): CustomerOutput
    {
        $payload = $this->map($input, CustomerPayload::class);

        $customer = Customer::fromPayload($payload);
        $this->customerRepository->save($customer);

        return $this->map($customer, CustomerOutput::class);
    }
}
