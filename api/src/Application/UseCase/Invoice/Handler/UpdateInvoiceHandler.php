<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\InvoiceGuard;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Document\DocumentFetcher;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\UpdateInvoiceTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;

/** @implements UseCaseHandlerInterface<UpdateInvoiceTask, InvoiceOutput> */
final readonly class UpdateInvoiceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private DocumentFetcher $documentFetcher,
        private InvoicePayloadMapper $payloadMapper,
        private InvoiceOutputMapper $outputMapper,
        private EntityFetcher $entityFetcher,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $task = TypeGuard::assertClass(UpdateInvoiceTask::class, $data);

        $invoice = InvoiceGuard::assertDraft(
            $this->documentFetcher->invoice($task->invoiceId)
        );

        $input = $task->input;
        $customer = $this->entityFetcher->customer($input->customerId);
        $user = $this->entityFetcher->user($input->userId);

        $payload = $this->payloadMapper->map($input, $customer, $user);
        $invoice->applyPayload($payload);

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map(
            $invoice,
            $this->workflowManager->invoiceActions($invoice)
        );
    }
}
