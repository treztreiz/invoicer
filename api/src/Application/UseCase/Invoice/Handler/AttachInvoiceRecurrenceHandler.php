<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Document\DocumentFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceRecurrenceMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\AttachInvoiceRecurrenceTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;

/** @implements UseCaseHandlerInterface<AttachInvoiceRecurrenceTask, InvoiceOutput> */
final readonly class AttachInvoiceRecurrenceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private DocumentFetcher $documentFetcher,
        private InvoiceOutputMapper $outputMapper,
        private InvoiceRecurrenceMapper $recurrenceMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(AttachInvoiceRecurrenceTask::class, $data);

        $invoice = $this->documentFetcher->invoice($task->invoiceId);
        $invoice = InvoiceGuard::guardAgainstScheduleConflicts($invoice, $task::class);
        $invoice = InvoiceGuard::assertCanAttachRecurrence($invoice, $task->replaceExisting);

        if ($task->replaceExisting && null !== $invoice->recurrence) {
            $invoice->detachRecurrence();
        }

        $payload = $this->recurrenceMapper->map($task->input);
        $invoice->attachRecurrence(InvoiceRecurrence::fromPayload($payload));

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->invoiceActions($invoice));
    }
}
