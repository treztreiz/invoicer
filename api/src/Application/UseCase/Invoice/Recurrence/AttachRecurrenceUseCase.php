<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Recurrence;

use App\Application\Dto\Invoice\Input\Recurrence\RecurrenceInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Entity\Document\Invoice\Recurrence;
use App\Domain\Payload\Invoice\Recurrence\RecurrencePayload;

final class AttachRecurrenceUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(RecurrenceInput $input, string $invoiceId): InvoiceOutput
    {
        $invoice = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $payload = $this->map($input, RecurrencePayload::class);
        $invoice->attachRecurrence(Recurrence::fromPayload($payload));

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
