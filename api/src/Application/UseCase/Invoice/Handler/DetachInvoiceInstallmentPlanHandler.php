<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\DetachInvoiceInstallmentPlanTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;

/** @implements UseCaseHandlerInterface<DetachInvoiceInstallmentPlanTask, InvoiceOutput> */
final readonly class DetachInvoiceInstallmentPlanHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private EntityFetcher $entityFetcher,
        private InvoiceOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(DetachInvoiceInstallmentPlanTask::class, $data);

        $invoice = InvoiceGuard::assertHasInstallmentPlan($this->entityFetcher->invoice($task->invoiceId));

        $invoice->detachInstallmentPlan();
        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->getInvoiceTransitions($invoice));
    }
}
