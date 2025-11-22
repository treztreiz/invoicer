<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice;

use App\Application\Dto\Invoice\Descriptor\InvoiceDescriptor;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Exception\DocumentRuleViolationException;
use App\Domain\Payload\Invoice\InvoicePayload;

final class GenerateInstallmentInvoiceUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(string $invoiceId): InvoiceOutput
    {
        $seed = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);
        if (!$seed->installmentPlan) {
            throw new DocumentRuleViolationException('Invoice does not have an installment plan.');
        }

        $installment = $seed->installmentPlan->getNextPendingInstallment();
        if (null === $installment) {
            throw new DocumentRuleViolationException('All installments have been generated.');
        }

        $descriptor = $this->map($seed, InvoiceDescriptor::class);
        $payload = $this->map($descriptor, InvoicePayload::class);

        $invoice = Invoice::fromInstallmentSeed($seed, $installment, $payload);

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
