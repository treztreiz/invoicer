<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Task;

use App\Application\UseCase\Invoice\Input\InvoiceRecurrenceInput;

final readonly class AttachInvoiceRecurrenceTask
{
    public function __construct(
        public string $invoiceId,
        public InvoiceRecurrenceInput $input,
        public bool $replaceExisting = false,
    ) {
    }
}
