<?php

declare(strict_types=1);

namespace App\Application\Service\Workflow;

use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class DocumentWorkflowManager
{
    public function __construct(
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
        #[Autowire(service: 'state_machine.quote_flow')]
        private WorkflowInterface $quoteWorkflow,
    ) {
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
