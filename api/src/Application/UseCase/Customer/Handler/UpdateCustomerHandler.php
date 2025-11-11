<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Application\UseCase\Customer\Input\Mapper\UpdateCustomerMapper;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Domain\Contracts\CustomerRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<CustomerInput,CustomerOutput> */
final readonly class UpdateCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
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

        $customerId = Uuid::fromString($customerInput->customerId);
        $customer = $this->customerRepository->findOneById($customerId);

        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $customerInput->customerId);
        }

        $payload = $this->mapper->map($customerInput);
        $customer->apply($payload);

        $this->customerRepository->save($customer);

        return $this->outputMapper->map($customer);
    }
}
