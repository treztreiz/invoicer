<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\State\Customer;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\UseCase\Customer\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Domain\Contracts\CustomerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<CustomerOutput> */
final readonly class CustomerStateProvider implements ProviderInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private CustomerOutputMapper $outputMapper,
    ) {
    }

    /** @return array<CustomerOutput>|CustomerOutput */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|CustomerOutput
    {
        if ($operation instanceof GetCollection) {
            $customers = $this->customerRepository->listActive();

            return array_map(
                fn ($customer) => $this->outputMapper->toOutput($customer),
                $customers
            );
        }

        if ($operation instanceof Get) {
            $id = $uriVariables['id'] ?? null;
            $uuid = Uuid::fromString((string) $id);
            $customer = $this->customerRepository->findOneById($uuid);

            if (null === $customer) {
                throw new NotFoundHttpException('Customer not found.');
            }

            return $this->outputMapper->toOutput($customer);
        }

        throw new \LogicException(sprintf('Unsupported operation %s for customer provider.', $operation::class));
    }
}
