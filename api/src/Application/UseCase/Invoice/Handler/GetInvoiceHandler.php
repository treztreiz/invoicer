<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Query\GetInvoiceQuery;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<GetInvoiceQuery, InvoiceOutput> */
final readonly class GetInvoiceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOutputMapper $outputMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $query = TypeGuard::assertClass(GetInvoiceQuery::class, $data);

        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($query->id));
        $invoice = InvoiceGuard::assertFound($invoice, $query->invoiceId);

        return $this->outputMapper->map($invoice, $this->actionsHelper->availableActions($invoice, $this->invoiceWorkflow));
    }
}
