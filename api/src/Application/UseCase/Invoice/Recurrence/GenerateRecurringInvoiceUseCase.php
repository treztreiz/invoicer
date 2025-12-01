<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Recurrence;

use App\Application\Dto\Invoice\Descriptor\InvoiceDescriptor;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Invoice\InvoicePayload;

class GenerateRecurringInvoiceUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(string $invoiceId, bool $allowBeforeNextRun = false): InvoiceOutput
    {
        $seed = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $recurrence = $seed->recurrence;
        if (!$recurrence) {
            throw new DocumentRuleViolationException('The seed does not have a recurrence configured.');
        }

        if (false === $recurrence->isRunnable($allowBeforeNextRun)) {
            throw new DocumentRuleViolationException('The recurrence is not runnable.');
        }

        $descriptor = $this->map($seed, InvoiceDescriptor::class);
        $payload = $this->map($descriptor, InvoicePayload::class);

        $invoice = Invoice::fromRecurrenceSeed($seed, $payload);

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
