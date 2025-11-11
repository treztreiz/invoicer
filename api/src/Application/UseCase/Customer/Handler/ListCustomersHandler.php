<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Task\ListCustomersTask;
use App\Domain\Contracts\CustomerRepositoryInterface;

/** @implements UseCaseHandlerInterface<\App\Application\UseCase\Customer\Task\ListCustomersTask,CustomerOutput> */
final readonly class ListCustomersHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    /** @return array<int, CustomerOutput> */
    public function handle(object $data): array
    {
        TypeGuard::assertClass(ListCustomersTask::class, $data);

        $customers = $this->customerRepository->listActive();

        return array_map(
            fn ($customer) => $this->outputMapper->map($customer),
            $customers
        );
    }
}
