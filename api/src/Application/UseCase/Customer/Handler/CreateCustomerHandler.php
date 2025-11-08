<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Application\UseCase\Customer\Input\Mapper\CreateCustomerMapper;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer\Customer;

/** @implements UseCaseHandlerInterface<CustomerInput,CustomerOutput> */
final readonly class CreateCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CreateCustomerMapper $mapper,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $customerInput = TypeGuard::assertClass(CustomerInput::class, $data);

        $payload = $this->mapper->map($customerInput);
        $customer = Customer::fromPayload($payload);

        $this->customerRepository->save($customer);

        return $this->outputMapper->map($customer);
    }
}
