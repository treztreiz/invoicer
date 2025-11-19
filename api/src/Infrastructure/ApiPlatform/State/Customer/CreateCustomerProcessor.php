<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Dto\Customer\Input\CustomerInput;
use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\CreateCustomerUseCase;

/**
 * @implements ProcessorInterface<CustomerInput, CustomerOutput>
 */
final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(private CreateCustomerUseCase $createCustomerHandler)
    {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CustomerOutput
    {
        $customerInput = TypeGuard::assertClass(CustomerInput::class, $data);

        return $this->createCustomerHandler->handle($customerInput);
    }
}
