<?php

declare(strict_types=1);

namespace App\Application\Dto\Quote\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TransitionQuoteInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(['send', 'accept', 'reject'])]
        private(set) string $transition,
    ) {
    }
}
