<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Command;

use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;

final readonly class AttachInvoiceRecurrenceCommand
{
    public function __construct(
        public string $invoiceId,
        public InvoiceRecurrenceInput $input,
    ) {
    }
}
