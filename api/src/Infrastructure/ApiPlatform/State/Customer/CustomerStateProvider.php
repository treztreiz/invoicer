<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Customer\Handler\GetCustomerHandler;
use App\Application\UseCase\Customer\Handler\ListCustomersHandler;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Application\UseCase\Customer\Task\GetCustomerTask;
use App\Application\UseCase\Customer\Task\ListCustomersTask;

/** @implements ProviderInterface<CustomerOutput> */
final readonly class CustomerStateProvider implements ProviderInterface
{
    public function __construct(
        private ListCustomersHandler $listCustomersHandler,
        private GetCustomerHandler $getCustomerHandler,
    ) {
    }

    /** @return array<CustomerOutput>|CustomerOutput */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|CustomerOutput
    {
        if ($operation instanceof GetCollection) {
            return $this->listCustomersHandler->handle(new ListCustomersTask());
        }

        if ($operation instanceof Get) {
            $task = new GetCustomerTask((string) ($uriVariables['id'] ?? ''));

            return $this->getCustomerHandler->handle($task);
        }

        throw new \LogicException(sprintf('Unsupported operation %s for customer provider.', $operation::class));
    }
}
