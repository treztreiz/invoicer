<?php

declare(strict_types=1);

namespace App\Application\UseCase\Document\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class DocumentLineOutput
{
    public function __construct(
        #[Groups(['quote:read', 'invoice:read'])]
        public string $description,

        #[Groups(['quote:read', 'invoice:read'])]
        public string $quantity,

        #[Groups(['quote:read', 'invoice:read'])]
        public string $rateUnit,

        #[Groups(['quote:read', 'invoice:read'])]
        public string $rate,

        #[Groups(['quote:read', 'invoice:read'])]
        public string $net,

        #[Groups(['quote:read', 'invoice:read'])]
        public string $tax,

        #[Groups(['quote:read', 'invoice:read'])]
        public string $gross,
    ) {
    }
}
