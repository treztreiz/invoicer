<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Task\CustomerStatusTask;
use App\Domain\Contracts\CustomerRepositoryInterface;

/** @implements UseCaseHandlerInterface<CustomerStatusTask, CustomerOutput> */
final readonly class ArchiveCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private EntityFetcher $entityFetcher,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $task = TypeGuard::assertClass(CustomerStatusTask::class, $data);

        $customer = $this->entityFetcher->customer($task->customerId);

        $customer->archive();
        $this->customerRepository->save($customer);

        return $this->outputMapper->map($customer);
    }
}
