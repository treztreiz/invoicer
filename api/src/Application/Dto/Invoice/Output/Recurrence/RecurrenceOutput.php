<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Recurrence;

use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\Invoice\Recurrence;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: Recurrence::class)]
final readonly class RecurrenceOutput
{
    public function __construct(
        #[Map(transform: [OutputTransformer::class, 'backedEnum'])]
        private(set) string $frequency,

        private(set) int $interval,

        #[Map(transform: [OutputTransformer::class, 'date'])]
        private(set) string $anchorDate,

        #[Map(transform: [OutputTransformer::class, 'backedEnum'])]
        private(set) string $endStrategy,

        #[Map(transform: [OutputTransformer::class, 'dateTime'])]
        private(set) ?string $nextRunAt,

        #[Map(transform: [OutputTransformer::class, 'date'])]
        private(set) ?string $endDate,

        private(set) ?int $occurrenceCount,
    ) {
    }
}
