<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class QuoteTransitionInput
{
    public function __construct(
        #[Groups(['quote:transition'])]
        #[Assert\NotBlank]
        #[Assert\Choice(['send', 'accept', 'reject'])]
        private(set) string $transition,
    ) {
    }
}
