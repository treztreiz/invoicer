<?php

declare(strict_types=1);

use ApiPlatform\Doctrine\Orm\Filter\BackedEnumFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use App\Application\Dto\Invoice\Input\Installment\InstallmentPlanInput;
use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Input\Recurrence\RecurrenceInput;
use App\Application\Dto\Invoice\Input\TransitionInvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Infrastructure\ApiPlatform\Filter\CustomerSearchFilter;
use App\Infrastructure\ApiPlatform\State\Invoice\CreateInvoiceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\AttachInstallmentPlanProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\DetachInstallmentPlanProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\GenerateInstallmentInvoiceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\UpdateInstallmentPlanProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Recurrence\AttachRecurrenceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Recurrence\DetachRecurrenceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Recurrence\UpdateRecurrenceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\TransitionInvoiceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\UpdateInvoiceProcessor;
use Symfony\Component\HttpFoundation\Response;

return new ApiResource(
    uriTemplate: '',
    shortName: 'Invoice',
    operations: [
        new GetCollection(
            stateOptions: new Options(Invoice::class),
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
                'issuedAt' => new QueryParameter(key: 'issuedAt', filter: new DateFilter(), property: 'issuedAt'),
                'dueDate' => new QueryParameter(key: 'dueDate', filter: new DateFilter(), property: 'dueDate'),
                'paidAt' => new QueryParameter(key: 'paidAt', filter: new DateFilter(), property: 'paidAt'),
                'recurrence' => new QueryParameter(key: 'recurrence', filter: new ExistsFilter(), property: 'recurrence'),
                'installmentPlan' => new QueryParameter(key: 'installmentPlan', filter: new ExistsFilter(), property: 'installmentPlan'),
                'recurrenceSeedId' => new QueryParameter(key: 'recurrenceSeedId', filter: new ExactFilter(), property: 'recurrenceSeedId'),
                'installmentSeedId' => new QueryParameter(key: 'installmentSeedId', filter: new ExactFilter(), property: 'installmentSeedId'),
            ]
        ),
        new Get(
            uriTemplate: '/{invoiceId}',
            uriVariables: ['invoiceId' => new Link(property: 'id')],
            stateOptions: new Options(Invoice::class),
        ),
        new Post(
            read: false,
            processor: CreateInvoiceProcessor::class
        ),
        new Put(
            uriTemplate: '/{invoiceId}',
            read: false,
            processor: UpdateInvoiceProcessor::class,
        ),
        new Post(
            uriTemplate: '/{invoiceId}/transition',
            status: Response::HTTP_OK,
            input: TransitionInvoiceInput::class,
            read: false,
            processor: TransitionInvoiceProcessor::class,
        ),
        // Recurrence
        new Post(
            uriTemplate: '/{invoiceId}/recurrence',
            status: Response::HTTP_OK,
            input: RecurrenceInput::class,
            read: false,
            processor: AttachRecurrenceProcessor::class,
        ),
        new Put(
            uriTemplate: '/{invoiceId}/recurrence',
            status: Response::HTTP_OK,
            input: RecurrenceInput::class,
            read: false,
            processor: UpdateRecurrenceProcessor::class,
        ),
        new Delete(
            uriTemplate: '/{invoiceId}/recurrence',
            status: Response::HTTP_OK,
            input: false,
            read: false,
            deserialize: false,
            processor: DetachRecurrenceProcessor::class,
        ),
        // Installments
        new Post(
            uriTemplate: '/{invoiceId}/installment-plan',
            status: Response::HTTP_OK,
            input: InstallmentPlanInput::class,
            read: false,
            processor: AttachInstallmentPlanProcessor::class,
        ),
        new Put(
            uriTemplate: '/{invoiceId}/installment-plan',
            status: Response::HTTP_OK,
            input: InstallmentPlanInput::class,
            read: false,
            processor: UpdateInstallmentPlanProcessor::class,
        ),
        new Delete(
            uriTemplate: '/{invoiceId}/installment-plan',
            status: Response::HTTP_OK,
            input: false,
            read: false,
            deserialize: false,
            processor: DetachInstallmentPlanProcessor::class,
        ),
        new Post(
            uriTemplate: '/{invoiceId}/installment-plan/generate',
            status: Response::HTTP_CREATED,
            input: false,
            read: false,
            deserialize: false,
            processor: GenerateInstallmentInvoiceProcessor::class,
        ),
    ],
    routePrefix: '/invoices',
    class: InvoiceOutput::class,
    input: InvoiceInput::class
);
