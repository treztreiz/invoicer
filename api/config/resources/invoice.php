<?php

declare(strict_types=1);

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Application\Dto\Invoice\Input\Installment\InstallmentPlanInput;
use App\Application\Dto\Invoice\Input\InvoiceInput;
use App\Application\Dto\Invoice\Input\Recurrence\InvoiceRecurrenceInput;
use App\Application\Dto\Invoice\Input\TransitionInvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Domain\Entity\Document\Invoice;
use App\Infrastructure\ApiPlatform\State\Invoice\CreateInvoiceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\AttachInvoiceInstallmentPlanProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\DetachInstallmentPlanProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Installment\UpdateInstallmentPlanProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Recurrence\AttachInvoiceRecurrenceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\Recurrence\DetachInvoiceRecurrenceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\TransitionInvoiceProcessor;
use App\Infrastructure\ApiPlatform\State\Invoice\UpdateInvoiceProcessor;
use Symfony\Component\HttpFoundation\Response;

return new ApiResource(
    uriTemplate: '',
    shortName: 'Invoice',
    operations: [
        new GetCollection(
            stateOptions: new Options(Invoice::class),
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
        new Post(
            uriTemplate: '/{invoiceId}/recurrence',
            status: Response::HTTP_OK,
            input: InvoiceRecurrenceInput::class,
            read: false,
            processor: AttachInvoiceRecurrenceProcessor::class,
        ),
        new Put(
            uriTemplate: '/{invoiceId}/recurrence',
            status: Response::HTTP_OK,
            input: InvoiceRecurrenceInput::class,
            read: false,
            processor: AttachInvoiceRecurrenceProcessor::class,
        ),
        new Delete(
            uriTemplate: '/{invoiceId}/recurrence',
            status: Response::HTTP_OK,
            input: false,
            read: false,
            deserialize: false,
            processor: DetachInvoiceRecurrenceProcessor::class,
        ),
        new Post(
            uriTemplate: '/{invoiceId}/installment-plan',
            status: Response::HTTP_OK,
            input: InstallmentPlanInput::class,
            read: false,
            processor: AttachInvoiceInstallmentPlanProcessor::class,
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
    ],
    routePrefix: '/invoices',
    class: InvoiceOutput::class,
    input: InvoiceInput::class
);
