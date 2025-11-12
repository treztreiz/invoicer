<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Customer\Handler\ArchiveCustomerHandler;
use App\Application\UseCase\Customer\Handler\RestoreCustomerHandler;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Task\CustomerStatusTask;

/**
 * @implements ProcessorInterface<object, CustomerOutput>
 */
final readonly class CustomerStatusStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ArchiveCustomerHandler $archiveCustomerHandler,
        private RestoreCustomerHandler $restoreCustomerHandler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CustomerOutput
    {
        $customerId = (string) ($uriVariables['customerId'] ?? '');

        if ('' === $customerId) {
            throw new \InvalidArgumentException('Customer id is required.');
        }

        $task = new CustomerStatusTask($customerId);

        return match ($operation->getName()) {
            'api_customers_archive' => $this->archiveCustomerHandler->handle($task),
            'api_customers_restore' => $this->restoreCustomerHandler->handle($task),
            default => throw new \LogicException(sprintf('Unsupported operation "%s" for customer status processor.', $operation->getName())),
        };
    }
}
