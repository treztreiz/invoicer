<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Command;

use App\Application\UseCase\Invoice\Input\InvoiceInput;

final readonly class UpdateInvoiceCommand
{
    public function __construct(
        public string $invoiceId,
        public InvoiceInput $input,
    ) {
    }
}
