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
        public string $installmentPlanId,

        #[Groups(['invoice:read'])]
        public array $installments,
    ) {
    }
}
