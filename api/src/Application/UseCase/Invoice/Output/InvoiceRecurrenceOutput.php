<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceRecurrenceOutput
{
    public function __construct(
        #[Groups(['invoice:read'])]
        private(set) string $frequency,
        #[Groups(['invoice:read'])]
        private(set) int $interval,
        #[Groups(['invoice:read'])]
        private(set) string $anchorDate,
        #[Groups(['invoice:read'])]
        private(set) string $endStrategy,
        #[Groups(['invoice:read'])]
        private(set) ?string $nextRunAt,
        #[Groups(['invoice:read'])]
        private(set) ?string $endDate,
        #[Groups(['invoice:read'])]
        private(set) ?int $occurrenceCount,
    ) {
    }
}
