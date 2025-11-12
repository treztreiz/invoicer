<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceInstallmentInput
{
    public function __construct(
        #[Groups(['invoice:installment'])]
        #[Assert\Positive]
        public float $percentage,

        #[Groups(['invoice:installment'])]
        public ?string $dueDate = null,
    ) {
    }
}
