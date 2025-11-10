<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Query\ListInvoicesQuery;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<ListInvoicesQuery, InvoiceOutput> */
final readonly class ListInvoicesHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
    ) {
    }

    /**
     * @return list<InvoiceOutput>
     */
    public function handle(object $data): array
    {
        TypeGuard::assertClass(ListInvoicesQuery::class, $data);

        $invoices = $this->invoiceRepository->list();

        return array_map(
            fn ($invoice) => $this->outputMapper->map(
                $invoice,
                array_values(
                    array_map(
                        static fn ($transition) => $transition->getName(),
                        $this->invoiceWorkflow->getEnabledTransitions($invoice)
                    )
                ),
            ),
            $invoices
        );
    }
}
