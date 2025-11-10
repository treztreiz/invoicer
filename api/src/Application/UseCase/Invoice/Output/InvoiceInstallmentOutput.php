<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceInstallmentOutput
{
    public function __construct(
        #[Groups(['invoice:read'])]
        public int $position,

        #[Groups(['invoice:read'])]
        public string $percentage,

        #[Groups(['invoice:read'])]
        public InvoiceTotalsOutput $amount,

        #[Groups(['invoice:read'])]
        public ?string $dueDate,
    ) {
    }
}
