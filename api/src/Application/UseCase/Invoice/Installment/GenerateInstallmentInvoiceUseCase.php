<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Installment;

use App\Application\Dto\Invoice\Descriptor\InvoiceDescriptor;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Payload\Invoice\InvoicePayload;

final class GenerateInstallmentInvoiceUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(string $invoiceId): InvoiceOutput
    {
        $seed = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $descriptor = $this->map($seed, InvoiceDescriptor::class);
        $payload = $this->map($descriptor, InvoicePayload::class);

        $invoice = Invoice::fromInstallmentSeed($seed, $payload);

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
