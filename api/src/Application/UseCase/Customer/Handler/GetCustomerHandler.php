<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Task\GetCustomerTask;
use App\Domain\Contracts\CustomerRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<GetCustomerTask,CustomerOutput> */
final readonly class GetCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $task = TypeGuard::assertClass(GetCustomerTask::class, $data);

        $customerId = Uuid::fromString($task->customerId);
        $customer = $this->customerRepository->findOneById($customerId);

        if (null === $customer) {
            throw new ResourceNotFoundException('Customer', $task->customerId);
        }

        return $this->outputMapper->map($customer);
    }
}
