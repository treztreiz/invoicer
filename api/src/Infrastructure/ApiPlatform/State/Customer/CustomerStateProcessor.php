<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Customer\Handler\CreateCustomerHandler;
use App\Application\UseCase\Customer\Handler\UpdateCustomerHandler;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Application\UseCase\Customer\Output\CustomerOutput;

/**
 * @implements ProcessorInterface<CustomerInput, CustomerOutput>
 */
final readonly class CustomerStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateCustomerHandler $createCustomerHandler,
        private UpdateCustomerHandler $updateCustomerHandler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CustomerOutput
    {
        $customerInput = TypeGuard::assertClass(CustomerInput::class, $data);

        if (isset($uriVariables['customerId'])) {
            $customerInput->customerId = (string)$uriVariables['customerId'];
        }

        return match ($operation::class) {
            Post::class => $this->createCustomerHandler->handle($customerInput),
            Put::class => $this->updateCustomerHandler->handle($customerInput),
            default => throw new \LogicException(sprintf('Unsupported operation "%s" for customer processor.', $operation::class)),
        };
    }
}
