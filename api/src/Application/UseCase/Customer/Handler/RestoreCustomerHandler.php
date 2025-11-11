<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Task\CustomerStatusTask;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<CustomerStatusTask, CustomerOutput> */
final readonly class RestoreCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $task = TypeGuard::assertClass(CustomerStatusTask::class, $data);

        $customerId = Uuid::fromString($task->customerId);
        $customer = $this->customerRepository->findOneById($customerId);

        if (!$customer instanceof Customer) {
            throw new ResourceNotFoundException('Customer', $task->customerId);
        }

        $customer->unarchive();
        $this->customerRepository->save($customer);

        return $this->outputMapper->map($customer);
    }
}
