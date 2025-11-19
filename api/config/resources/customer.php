<?php

declare(strict_types=1);

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Application\Dto\Customer\Input\CustomerInput;
use App\Application\Dto\Customer\Output\CustomerOutput;
use App\Domain\Entity\Customer\Customer;
use App\Infrastructure\ApiPlatform\State\Customer\ArchiveCustomerProcessor;
use App\Infrastructure\ApiPlatform\State\Customer\CreateCustomerProcessor;
use App\Infrastructure\ApiPlatform\State\Customer\RestoreCustomerProcessor;
use App\Infrastructure\ApiPlatform\State\Customer\UpdateCustomerProcessor;
use Symfony\Component\HttpFoundation\Response;

return new ApiResource(
    uriTemplate: '',
    shortName: 'Customer',
    operations: [
        new GetCollection(
            stateOptions: new Options(Customer::class),
        ),
        new Get(
            uriTemplate: '/{customerId}',
            uriVariables: ['customerId' => new Link(property: 'id')],
            stateOptions: new Options(Customer::class)
        ),
        new Post(
            read: false,
            processor: CreateCustomerProcessor::class
        ),
        new Put(
            uriTemplate: '/{customerId}',
            read: false,
            processor: UpdateCustomerProcessor::class,
        ),
        new Post(
            uriTemplate: '/{customerId}/archive',
            status: Response::HTTP_OK,
            input: false,
            read: false,
            deserialize: false,
            processor: ArchiveCustomerProcessor::class,
        ),
        new Post(
            uriTemplate: '/{customerId}/restore',
            status: Response::HTTP_OK,
            input: false,
            read: false,
            deserialize: false,
            processor: RestoreCustomerProcessor::class,
        ),
    ],
    routePrefix: '/customers',
    class: CustomerOutput::class,
    input: CustomerInput::class
);
