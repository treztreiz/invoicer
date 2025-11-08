<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class QuoteTotalsOutput
{
    public function __construct(
        #[Groups(['quote:read'])]
        public string $net,

        #[Groups(['quote:read'])]
        public string $tax,

        #[Groups(['quote:read'])]
        public string $gross,
    ) {
    }
}
