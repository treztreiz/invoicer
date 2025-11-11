<?php

declare(strict_types=1);

namespace App\Application\Service\Workflow;

use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\Attribute\Required;

final class DocumentWorkflowManager
{
    private ?WorkflowInterface $invoiceWorkflow = null;

    private ?WorkflowInterface $quoteWorkflow = null;

    #[Required]
    public function setInvoiceWorkflow(
        #[Autowire(service: 'state_machine.invoice_flow')]
        WorkflowInterface $invoiceWorkflow,
    ): void {
        $this->invoiceWorkflow = $invoiceWorkflow;
    }

    #[Required]
    public function setQuoteWorkflow(
        #[Autowire(service: 'state_machine.quote_flow')]
        WorkflowInterface $quoteWorkflow,
    ): void {
        $this->quoteWorkflow = $quoteWorkflow;
    }

    /** @return list<string> */
    public function invoiceActions(Invoice $invoice): array
    {
        return $this->transitionNames($this->invoiceWorkflow->getEnabledTransitions($invoice));
    }

    /** @return list<string> */
    public function quoteActions(Quote $quote): array
    {
        return $this->transitionNames($this->quoteWorkflow->getEnabledTransitions($quote));
    }

    public function canInvoiceTransition(Invoice $invoice, string $transition): bool
    {
        return $this->invoiceWorkflow->can($invoice, $transition);
    }

    public function canQuoteTransition(Quote $quote, string $transition): bool
    {
        return $this->quoteWorkflow->can($quote, $transition);
    }

    /**
     * @param iterable<Transition> $transitions
     *
     * @return list<string>
     */
    private function transitionNames(iterable $transitions): array
    {
        $names = [];
        foreach ($transitions as $transition) {
            $names[] = $transition->getName();
        }

        return $names;
    }
}
