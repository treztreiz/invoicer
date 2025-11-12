<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Application\UseCase\Customer\Input\Mapper\UpdateCustomerMapper;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Domain\Contracts\CustomerRepositoryInterface;

/** @implements UseCaseHandlerInterface<CustomerInput,CustomerOutput> */
final readonly class UpdateCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private EntityFetcher $entityFetcher,
        private UpdateCustomerMapper $mapper,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $customerInput = TypeGuard::assertClass(CustomerInput::class, $data);

        if ('' === $customerInput->customerId) {
            throw new \InvalidArgumentException('Customer id is required for update operations.');
        }

        $customer = $this->entityFetcher->customer($customerInput->customerId);

        $payload = $this->mapper->map($customerInput);
        $customer->apply($payload);

        $this->customerRepository->save($customer);

        return $this->outputMapper->map($customer);
    }
}
