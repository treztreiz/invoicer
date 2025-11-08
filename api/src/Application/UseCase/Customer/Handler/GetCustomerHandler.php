<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Query\GetCustomerQuery;
use App\Domain\Contracts\CustomerRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<GetCustomerQuery,CustomerOutput> */
final readonly class GetCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $query = TypeGuard::assertClass(GetCustomerQuery::class, $data);

        $customerId = Uuid::fromString($query->id);
        $customer = $this->customerRepository->findOneById($customerId);

        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $query->id);
        }

        return $this->outputMapper->map($customer);
    }
}
