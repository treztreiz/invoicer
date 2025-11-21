<?php

declare(strict_types=1);

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Dto\Quote\Input\TransitionQuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Domain\Entity\Document\Quote\Quote;
use App\Infrastructure\ApiPlatform\Filter\CustomerSearchFilter;
use App\Infrastructure\ApiPlatform\State\Quote\CreateQuoteProcessor;
use App\Infrastructure\ApiPlatform\State\Quote\TransitionQuoteProcessor;
use App\Infrastructure\ApiPlatform\State\Quote\UpdateQuoteProcessor;
use Symfony\Component\HttpFoundation\Response;

return new ApiResource(
    uriTemplate: '',
    shortName: 'Quote',
    operations: [
        new GetCollection(
            stateOptions: new Options(Quote::class),
            parameters: [
                'reference' => new QueryParameter(key: 'reference', filter: new PartialSearchFilter(), property: 'reference'),
                'title' => new QueryParameter(key: 'title', filter: new FreeTextQueryFilter(new OrFilter(new PartialSearchFilter())), properties: ['title', 'subtitle']),
                'customerId' => new QueryParameter(key: 'customerId', filter: new ExactFilter(), property: 'customer'),
                'customer' => new QueryParameter(key: 'customer', filter: CustomerSearchFilter::class, property: 'search'),
                'totalNet' => new QueryParameter(key: 'totalNet', filter: new RangeFilter(), property: 'total.net.value'),
                'totalGross' => new QueryParameter(key: 'totalGross', filter: new RangeFilter(), property: 'total.gross.value'),
                'status' => new QueryParameter(key: 'status', filter: new BackedEnumFilter(), property: 'status'),
                'archived' => new QueryParameter(key: 'archived', filter: new BooleanFilter(), property: 'isArchived'),
                'createdAt' => new QueryParameter(key: 'createdAt', filter: new DateFilter(), property: 'createdAt'),
            ]
        ),
        new Get(
            uriTemplate: '/{quoteId}',
            uriVariables: ['quoteId' => new Link(property: 'id')],
            stateOptions: new Options(Quote::class),
        ),
        new Post(
            read: false,
            processor: CreateQuoteProcessor::class,
        ),
        new Put(
            uriTemplate: '/{quoteId}',
            read: false,
            processor: UpdateQuoteProcessor::class,
        ),
        new Post(
            uriTemplate: '/{quoteId}/transition',
            status: Response::HTTP_OK,
            input: TransitionQuoteInput::class,
            read: false,
            processor: TransitionQuoteProcessor::class,
        ),
    ],
    routePrefix: '/quotes',
    class: QuoteOutput::class,
    input: QuoteInput::class
);
