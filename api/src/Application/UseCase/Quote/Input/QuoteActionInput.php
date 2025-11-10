<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class QuoteActionInput
{
    public function __construct(
        #[Groups(['quote:action'])]
        #[Assert\NotBlank]
        #[Assert\Choice(['send', 'accept', 'reject'])]
        public string $action,
    ) {
    }
}
