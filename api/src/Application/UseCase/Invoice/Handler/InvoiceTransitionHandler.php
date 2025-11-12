<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\InvoiceTransitionTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;

/** @implements UseCaseHandlerInterface<InvoiceTransitionTask, InvoiceOutput> */
final readonly class InvoiceTransitionHandler implements UseCaseHandlerInterface
{
    private const string TRANSITION_ISSUE = 'issue';
    private const string TRANSITION_MARK_PAID = 'mark_paid';
    private const string TRANSITION_VOID = 'void';

    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private EntityFetcher $entityFetcher,
        private InvoiceOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(InvoiceTransitionTask::class, $data);

        $invoice = $this->entityFetcher->invoice($task->invoiceId);

        if (!$this->workflowManager->canInvoiceTransition($invoice, $task->transition)) {
            throw new DomainRuleViolationException(sprintf('Invoice cannot transition via "%s".', $task->transition));
        }

        $this->applyTransition($invoice, $task->transition);
        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->getInvoiceTransitions($invoice));
    }

    private function applyTransition(Invoice $invoice, string $transition): void
    {
        $now = new \DateTimeImmutable();

        match ($transition) {
            self::TRANSITION_ISSUE => $invoice->issue($now, $invoice->dueDate ?? $now),
            self::TRANSITION_MARK_PAID => $invoice->markPaid($now),
            self::TRANSITION_VOID => $invoice->void(),
            default => throw new DomainRuleViolationException(sprintf('Unknown transition "%s".', $transition)),
        };
    }
}
