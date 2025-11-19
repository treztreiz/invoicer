<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TransitionInvoiceInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(['issue', 'mark_paid', 'void'])]
        private(set) string $transition,
    ) {
    }
}
