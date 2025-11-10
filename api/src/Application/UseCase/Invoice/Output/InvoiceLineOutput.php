<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceLineOutput
{
    public function __construct(
        #[Groups(['invoice:read'])]
        public string $description,

        #[Groups(['invoice:read'])]
        public string $quantity,

        #[Groups(['invoice:read'])]
        public string $rateUnit,

        #[Groups(['invoice:read'])]
        public string $rate,

        #[Groups(['invoice:read'])]
        public string $net,

        #[Groups(['invoice:read'])]
        public string $tax,

        #[Groups(['invoice:read'])]
        public string $gross,
    ) {
    }
}
