<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Guard\TypeGuard;
use App\Application\UseCase\Invoice\Command\AttachInvoiceInstallmentPlanCommand;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceInstallmentPlanMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\Workflow\WorkflowActionsHelper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

/** @implements UseCaseHandlerInterface<AttachInvoiceInstallmentPlanCommand, InvoiceOutput> */
final readonly class AttachInvoiceInstallmentPlanHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOutputMapper $outputMapper,
        private InvoiceInstallmentPlanMapper $planMapper,
        #[Autowire(service: 'state_machine.invoice_flow')]
        private WorkflowInterface $invoiceWorkflow,
        private WorkflowActionsHelper $actionsHelper,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $command = TypeGuard::assertClass(AttachInvoiceInstallmentPlanCommand::class, $data);

        $invoice = $this->invoiceRepository->findOneById(Uuid::fromString($command->invoiceId));

        if (!$invoice instanceof Invoice) {
            throw new ResourceNotFoundException('Invoice', $command->invoiceId);
        }

        $this->guardAgainstScheduleConflicts($invoice);

        if (null !== $invoice->installmentPlan && !$command->replaceExisting) {
            throw new BadRequestHttpException('Invoice already has an installment plan.');
        }

        if ($command->replaceExisting && null !== $invoice->installmentPlan) {
            $invoice->detachInstallmentPlan();
        }

        $invoice->attachInstallmentPlan($this->planMapper->map($command->input, $invoice));

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->actionsHelper->availableActions($invoice, $this->invoiceWorkflow));
    }

    private function guardAgainstScheduleConflicts(Invoice $invoice): void
    {
        if (null !== $invoice->recurrence) {
            throw new BadRequestHttpException('Invoices cannot have both an installment plan and a recurrence.');
        }

        if (null !== $invoice->recurrenceSeedId || null !== $invoice->installmentSeedId) {
            throw new BadRequestHttpException('Generated invoices cannot attach new scheduling rules.');
        }
    }
}
