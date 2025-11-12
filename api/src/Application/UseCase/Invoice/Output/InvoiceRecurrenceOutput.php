<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceRecurrenceOutput
{
    public function __construct(
        #[Groups(['invoice:read'])]
        public string $frequency,

        #[Groups(['invoice:read'])]
        public int $interval,

        #[Groups(['invoice:read'])]
        public string $anchorDate,

        #[Groups(['invoice:read'])]
        public string $endStrategy,

        #[Groups(['invoice:read'])]
        public ?string $nextRunAt,

        #[Groups(['invoice:read'])]
        public ?string $endDate,

        #[Groups(['invoice:read'])]
        public ?int $occurrenceCount,
    ) {
    }
}
