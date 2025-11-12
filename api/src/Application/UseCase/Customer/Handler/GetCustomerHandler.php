<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Task\GetCustomerTask;

/** @implements UseCaseHandlerInterface<GetCustomerTask,CustomerOutput> */
final readonly class GetCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private EntityFetcher $entityFetcher,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $task = TypeGuard::assertClass(GetCustomerTask::class, $data);

        $customer = $this->entityFetcher->customer($task->customerId);

        return $this->outputMapper->map($customer);
    }
}
