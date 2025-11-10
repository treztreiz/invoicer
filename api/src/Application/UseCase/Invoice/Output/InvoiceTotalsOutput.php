<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceTotalsOutput
{
    public function __construct(
        #[Groups(['invoice:read'])]
        public string $net,

        #[Groups(['invoice:read'])]
        public string $tax,

        #[Groups(['invoice:read'])]
        public string $gross,
    ) {
    }
}
