<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Task;

final readonly class InvoiceTransitionTask
{
    public function __construct(
        public string $invoiceId,
        public string $transition,
    ) {
    }
}
