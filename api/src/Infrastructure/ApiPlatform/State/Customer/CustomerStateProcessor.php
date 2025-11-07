<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\UseCase\Customer\Handler\CreateCustomerHandler;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Application\UseCase\Customer\Mapper\CustomerInputMapper;
use App\Application\UseCase\Customer\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Output\CustomerOutput;

/**
 * @implements ProcessorInterface<CustomerInput, CustomerOutput>
 */
final readonly class CustomerStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerInputMapper $inputMapper,
        private CustomerOutputMapper $outputMapper,
        private CreateCustomerHandler $createCustomerHandler,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): CustomerOutput
    {
        /* @phpstan-ignore-next-line defensive runtime guard */
        if (!$data instanceof CustomerInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', CustomerInput::class, get_debug_type($data)));
        }

        $input = $this->inputMapper->fromPayload($data);
        $customer = $this->createCustomerHandler->handle($input);

        return $this->outputMapper->toOutput($customer);
    }
}
