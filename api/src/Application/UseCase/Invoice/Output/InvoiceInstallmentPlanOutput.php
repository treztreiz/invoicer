<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceInstallmentPlanOutput
{
    /**
     * @param list<InvoiceInstallmentOutput> $installments
     */
    public function __construct(
        #[Groups(['invoice:read'])]
        private(set) string $installmentPlanId,
        #[Groups(['invoice:read'])]
        private(set) array $installments,
    ) {
    }
}
