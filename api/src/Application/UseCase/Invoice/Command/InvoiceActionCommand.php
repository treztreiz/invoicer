<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Command;

final readonly class InvoiceActionCommand
{
    public function __construct(
        public string $invoiceId,
        public string $action,
    ) {
    }
}
