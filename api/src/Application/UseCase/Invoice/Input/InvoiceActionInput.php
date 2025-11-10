<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceActionInput
{
    public function __construct(
        #[Groups(['invoice:action'])]
        #[Assert\NotBlank]
        #[Assert\Choice(['issue', 'mark_paid', 'void'])]
        public string $action,
    ) {
    }
}
