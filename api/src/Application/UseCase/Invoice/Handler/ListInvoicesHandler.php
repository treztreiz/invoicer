<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Application\UseCase\Invoice\Task\ListInvoicesTask;
use App\Domain\Contracts\InvoiceRepositoryInterface;

/** @implements UseCaseHandlerInterface<ListInvoicesTask, InvoiceOutput> */
final readonly class ListInvoicesHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoiceOutputMapper $outputMapper,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    /**
     * @return list<InvoiceOutput>
     */
    public function handle(object $data): array
    {
        TypeGuard::assertClass(ListInvoicesTask::class, $data);

        $invoices = $this->invoiceRepository->list();

        return array_map(
            fn($invoice) => $this->outputMapper->map(
                $invoice,
                $this->workflowManager->invoiceActions($invoice)
            ),
            $invoices
        );
    }
}
