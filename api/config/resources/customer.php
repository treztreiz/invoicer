<?php

declare(strict_types=1);

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
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
            parameters: [
                'name' => new QueryParameter(key: 'name', filter: new FreeTextQueryFilter(new OrFilter(new PartialSearchFilter())), properties: [
                    'legalName',
                    'name.firstName',
                    'name.lastName',
                ]),
                'archived' => new QueryParameter(key: 'archived', filter: new BooleanFilter(), property: 'isArchived'),
                'createdAt' => new QueryParameter(key: 'createdAt', filter: new DateFilter(), property: 'createdAt'),
            ],
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
