<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InvoiceInstallmentPlanInput
{
    /**
     * @param list<InvoiceInstallmentInput|array<string, mixed>> $installments
     */
    public function __construct(
        #[Groups(['invoice:installment'])]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        private(set) array $installments,
    ) {
    }
}
