<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\Guard\TypeGuard;
use App\Application\Service\EntityFetcher;
use App\Application\Service\Workflow\DocumentWorkflowManager;
use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Input\Mapper\InvoicePayloadMapper;
use App\Application\UseCase\Invoice\Output\InvoiceOutput;
use App\Application\UseCase\Invoice\Output\Mapper\InvoiceOutputMapper;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Entity\Document\Invoice;

/** @implements UseCaseHandlerInterface<InvoiceInput, InvoiceOutput> */
final readonly class CreateInvoiceHandler implements UseCaseHandlerInterface
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository,
        private InvoicePayloadMapper $mapper,
        private InvoiceOutputMapper $outputMapper,
        private EntityFetcher $entityFetcher,
        private DocumentWorkflowManager $workflowManager,
    ) {
    }

    public function handle(object $data): InvoiceOutput
    {
        $input = TypeGuard::assertClass(InvoiceInput::class, $data);

        $customer = $this->entityFetcher->customer($input->customerId);
        $user = $this->entityFetcher->user($input->userId);

        $payload = $this->mapper->map($input, $customer, $user);

        $invoice = Invoice::fromPayload($payload);

        $this->invoiceRepository->save($invoice);

        return $this->outputMapper->map($invoice, $this->workflowManager->getInvoiceTransitions($invoice));
    }
}
