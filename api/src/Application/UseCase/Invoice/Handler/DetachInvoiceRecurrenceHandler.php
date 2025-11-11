<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceRecurrenceTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;

/** @implements UseCaseHandlerInterface<DetachInvoiceRecurrenceTask, InvoiceOutput> */
final readonly class DetachInvoiceRecurrenceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private EntityFetcher $entityFetcher,
        private InvoiceOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(DetachInvoiceRecurrenceTask::class, $data);

        $invoice = InvoiceGuard::assertHasRecurrence($this->entityFetcher->invoice($task->invoiceId));

        $invoice->detachRecurrence();
        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->invoiceActions($invoice));
    }
}
