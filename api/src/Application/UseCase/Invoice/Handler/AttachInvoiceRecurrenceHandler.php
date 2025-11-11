<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\AttachInvoiceRecurrenceCommand;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceRecurrenceMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<AttachInvoiceRecurrenceCommand, InvoiceOutput> */
final readonly class AttachInvoiceRecurrenceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOutputMapper $outputMapper,
        private InvoiceRecurrenceMapper $recurrenceMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $command = TypeGuard::assertClass(AttachInvoiceRecurrenceCommand::class, $data);

        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($command->invoiceId));
        $invoice = InvoiceGuard::assertFound($invoice, $command->invoiceId);
        $invoice = InvoiceGuard::guardAgainstScheduleConflicts($invoice, $command::class);

        $payload = $this->recurrenceMapper->map($command->input);
        $invoice->attachRecurrence(InvoiceRecurrence::fromPayload($payload));

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->actionsHelper->availableActions($invoice, $this->invoiceWorkflow));
    }
}
