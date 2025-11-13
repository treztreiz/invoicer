<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class DocumentLineOutput
{
    public function __construct(
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $description,
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $quantity,
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $rateUnit,
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $rate,
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $net,
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $tax,
        #[Groups(['quote:read', 'invoice:read'])]
        private(set) string $gross,
    ) {
    }
}
