<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class QuoteTotalsOutput
{
    public function __construct(
        #[Groups(['quote:read'])]
        private(set) string $net,
        #[Groups(['quote:read'])]
        private(set) string $tax,
        #[Groups(['quote:read'])]
        private(set) string $gross,
    ) {
    }
}
