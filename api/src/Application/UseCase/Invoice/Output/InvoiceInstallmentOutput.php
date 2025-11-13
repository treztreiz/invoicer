<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceInstallmentOutput
{
    public function __construct(
        #[Groups(['invoice:read'])]
        private(set) int $position,
        #[Groups(['invoice:read'])]
        private(set) string $percentage,
        #[Groups(['invoice:read'])]
        private(set) InvoiceTotalsOutput $amount,
        #[Groups(['invoice:read'])]
        private(set) ?string $dueDate,
    ) {
    }
}
