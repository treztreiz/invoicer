<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Query\GetInvoiceQuery;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
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
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $query = TypeGuard::assertClass(GetInvoiceQuery::class, $data);

        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($query->id));

        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $query->id);
        }

        $available = array_map(
            static fn ($transition) => $transition->getName(),
            $this->invoiceWorkflow->getEnabledTransitions($invoice)
        );

        return $this->outputMapper->map($invoice, $available);
    }
}
