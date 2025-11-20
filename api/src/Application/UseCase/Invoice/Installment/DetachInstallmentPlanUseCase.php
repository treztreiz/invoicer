<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Installment;

use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\Invoice;

final class DetachInstallmentPlanUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(string $invoiceId): InvoiceOutput
    {
        $invoice = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $invoice->detachInstallmentPlan();
        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
