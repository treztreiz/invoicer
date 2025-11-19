<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice;

use App\Application\Dto\Invoice\Input\TransitionInvoiceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Exception\DomainRuleViolationException;
use App\Application\Service\Trait\DocumentWorkflowManagerAwareTrait;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice;

final class TransitionInvoiceUseCase extends AbstractUseCase
{
    use DocumentWorkflowManagerAwareTrait;
    use InvoiceRepositoryAwareTrait;

    private const string TRANSITION_ISSUE = 'issue';
    private const string TRANSITION_MARK_PAID = 'mark_paid';
    private const string TRANSITION_VOID = 'void';

    public function handle(TransitionInvoiceInput $input, string $invoiceId): InvoiceOutput
    {
        $invoice = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $transition = $input->transition;

        if (!$this->documentWorkflowManager->canInvoiceTransition($invoice, $transition)) {
            throw new DomainRuleViolationException(sprintf('Invoice cannot transition via "%s".', $transition));
        }

        $this->applyTransition($invoice, $transition);
        $this->invoiceRepository->save($invoice);

        return $this->objectMapper->map($invoice, InvoiceOutput::class);
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
