<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Recurrence;

use App\Application\Dto\Invoice\Input\Recurrence\InvoiceRecurrenceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use App\Domain\Payload\Document\Invoice\InvoiceRecurrencePayload;

final class AttachInvoiceRecurrenceUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(InvoiceRecurrenceInput $input, string $invoiceId): InvoiceOutput
    {
        $invoice = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $payload = $this->map($input, InvoiceRecurrencePayload::class);
        $invoice->attachRecurrence(InvoiceRecurrence::fromPayload($payload));

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
