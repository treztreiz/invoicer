<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Customer\Action\CustomerStatusAction;
use App\Application\UseCase\Customer\Handler\ArchiveCustomerHandler;
use App\Application\UseCase\Customer\Handler\RestoreCustomerHandler;
use App\Application\UseCase\Customer\Output\CustomerOutput;

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
        $id = (string) ($uriVariables['id'] ?? '');

        if ('' === $id) {
            throw new \InvalidArgumentException('Customer id is required.');
        }

        $action = new CustomerStatusAction($id);

        return match ($operation->getName()) {
            'api_customers_archive' => $this->archiveCustomerHandler->handle($action),
            'api_customers_restore' => $this->restoreCustomerHandler->handle($action),
            default => throw new \LogicException(sprintf('Unsupported operation "%s" for customer status processor.', $operation->getName())),
        };
    }
}
