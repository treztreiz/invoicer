<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Command;

use App\Application\UseCase\Invoice\Input\InvoiceInstallmentPlanInput;

final readonly class AttachInvoiceInstallmentPlanCommand
{
    public function __construct(
        public string $invoiceId,
        public InvoiceInstallmentPlanInput $input,
        public bool $replaceExisting = false,
    ) {
    }
}
