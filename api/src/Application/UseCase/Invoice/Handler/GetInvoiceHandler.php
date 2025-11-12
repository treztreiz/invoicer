<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\GetInvoiceTask;

/** @implements UseCaseHandlerInterface<GetInvoiceTask, InvoiceOutput> */
final readonly class GetInvoiceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private EntityFetcher $entityFetcher,
        private InvoiceOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(GetInvoiceTask::class, $data);

        $invoice = $this->entityFetcher->invoice($task->invoiceId);

        return $this->outputMapper->map($invoice, $this->workflowManager->getInvoiceTransitions($invoice));
    }
}
