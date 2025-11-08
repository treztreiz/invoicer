<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Action\CustomerStatusAction;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use Symfony\Component\Uid\Uuid;

/** @implements UseCaseHandlerInterface<CustomerStatusAction, CustomerOutput> */
final readonly class ArchiveCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    public function handle(object $data): CustomerOutput
    {
        $action = TypeGuard::assertClass(CustomerStatusAction::class, $data);

        $customerId = Uuid::fromString($action->id);
        $customer = $this->customerRepository->findOneById($customerId);

        if (!$customer instanceof Customer) {
            throw new ResourceNotFoundException('Customer', $action->id);
        }

        $customer->archive();
        $this->customerRepository->save($customer);

        return $this->outputMapper->map($customer);
    }
}
