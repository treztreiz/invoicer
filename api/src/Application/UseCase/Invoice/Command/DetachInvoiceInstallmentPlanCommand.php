<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Command;

final readonly class DetachInvoiceInstallmentPlanCommand
{
    public function __construct(public string $invoiceId)
    {
    }
}
