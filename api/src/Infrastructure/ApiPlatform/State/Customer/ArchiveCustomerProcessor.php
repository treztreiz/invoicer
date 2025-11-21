<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Customer\Input\CustomerInput;
use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\ArchiveCustomerUseCase;

/**
 * @implements ProcessorInterface<CustomerInput, CustomerOutput>
 */
final readonly class ArchiveCustomerProcessor implements ProcessorInterface
{
    public function __construct(private ArchiveCustomerUseCase $archiveCustomerHandler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CustomerOutput
    {
        $customerId = (string) ($uriVariables['customerId'] ?? '');

        if ('' === $customerId) {
            throw new \InvalidArgumentException('Customer id is required.');
        }

        return $this->archiveCustomerHandler->handle($customerId);
    }
}
