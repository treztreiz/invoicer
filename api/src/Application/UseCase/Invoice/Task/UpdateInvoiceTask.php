<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Task;

use App\Application\UseCase\Invoice\Input\InvoiceInput;

final readonly class UpdateInvoiceTask
{
    public function __construct(
        public string $invoiceId,
        public InvoiceInput $input,
    ) {
    }
}
