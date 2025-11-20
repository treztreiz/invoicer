<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Installment;

use App\Application\Dto\Invoice\Input\Installment\InstallmentPlanInput;
use App\Application\Dto\Invoice\Output\InvoiceOutput;
use App\Application\Service\Trait\InvoiceRepositoryAwareTrait;
use App\Application\UseCase\AbstractUseCase;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Payload\Invoice\Installment\InstallmentPlanPayload;

final class AttachInstallmentPlanUseCase extends AbstractUseCase
{
    use InvoiceRepositoryAwareTrait;

    public function handle(InstallmentPlanInput $input, string $invoiceId): InvoiceOutput
    {
        $invoice = $this->findOneById($this->invoiceRepository, $invoiceId, Invoice::class);

        $payload = $this->map($input, InstallmentPlanPayload::class);
        $invoice->attachInstallmentPlan(InstallmentPlan::fromPayload($payload, $invoice->total));

        $this->invoiceRepository->save($invoice);

        return $this->map($invoice, InvoiceOutput::class);
    }
}
