<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InvoiceInstallmentInput
{
    public function __construct(
        #[Groups(['invoice:installment'])]
        #[Assert\Positive]
        private(set) float $percentage,

        #[Groups(['invoice:installment'])]
        private(set) ?string $dueDate = null,
    ) {
    }
}
