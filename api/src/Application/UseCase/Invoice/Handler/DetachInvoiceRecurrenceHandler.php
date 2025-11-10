<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\DetachInvoiceRecurrenceCommand;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<DetachInvoiceRecurrenceCommand, InvoiceOutput> */
final readonly class DetachInvoiceRecurrenceHandler implements UseCaseHandlerInterface
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
        $command = TypeGuard::assertClass(DetachInvoiceRecurrenceCommand::class, $data);

        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($command->invoiceId));

        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $command->invoiceId);
        }

        if (null === $invoice->recurrence) {
            throw new BadRequestHttpException('Invoice does not have a recurrence configured.');
        }

        $invoice->detachRecurrence();
        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->availableActions($invoice));
    }

    /**
     * @return list<string>
     */
    private function availableActions(Invoice $invoice): array
    {
        return array_values(
            array_map(
                static fn ($transition) => $transition->getName(),
                $this->invoiceWorkflow->getEnabledTransitions($invoice)
            )
        );
    }
}
