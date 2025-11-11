<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Input\Mapper\InvoiceInstallmentPlanMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\AttachInvoiceInstallmentPlanTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Enum\InvoiceScheduleType;

/** @implements UseCaseHandlerInterface<AttachInvoiceInstallmentPlanTask, InvoiceOutput> */
final readonly class AttachInvoiceInstallmentPlanHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private EntityFetcher $entityFetcher,
        private InvoiceOutputMapper $outputMapper,
        private InvoiceInstallmentPlanMapper $planMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(AttachInvoiceInstallmentPlanTask::class, $data);

        $invoice = $this->entityFetcher->invoice($task->invoiceId);
        $invoice = InvoiceGuard::guardAgainstScheduleConflicts($invoice, InvoiceScheduleType::INSTALLMENT);
        $invoice = InvoiceGuard::assertCanAttachInstallmentPlan($invoice, $task->replaceExisting);

        if ($task->replaceExisting && null !== $invoice->installmentPlan) {
            $invoice->detachInstallmentPlan();
        }

        $payload = $this->planMapper->map($task->input, $invoice);
        $invoice->attachInstallmentPlan(InstallmentPlan::fromPayload($payload));

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->invoiceActions($invoice));
    }
}
