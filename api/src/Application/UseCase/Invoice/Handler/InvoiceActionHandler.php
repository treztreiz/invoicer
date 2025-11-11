<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Document\DocumentFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\InvoiceActionTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;

/** @implements UseCaseHandlerInterface<InvoiceActionTask, InvoiceOutput> */
final readonly class InvoiceActionHandler implements UseCaseHandlerInterface
{
    private const string ACTION_ISSUE = 'issue';
    private const string ACTION_MARK_PAID = 'mark_paid';
    private const string ACTION_VOID = 'void';

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private DocumentFetcher $documentFetcher,
        private InvoiceOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(InvoiceActionTask::class, $data);

        $invoice = $this->documentFetcher->invoice($task->invoiceId);

        if (!$this->workflowManager->canInvoiceTransition($invoice, $task->action)) {
            throw new DomainRuleViolationException(sprintf('Invoice cannot transition via "%s".', $task->action));
        }

        $this->applyTransition($invoice, $task->action);
        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->invoiceActions($invoice));
    }

    private function applyTransition(Invoice $invoice, string $action): void
    {
        $now = new \DateTimeImmutable();

        match ($action) {
            self::ACTION_ISSUE => $invoice->issue($now, $invoice->dueDate ?? $now),
            self::ACTION_MARK_PAID => $invoice->markPaid($now),
            self::ACTION_VOID => $invoice->void(),
            default => throw new DomainRuleViolationException(sprintf('Unknown action "%s".', $action)),
        };
    }
}
