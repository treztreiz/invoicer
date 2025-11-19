<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Input\Recurrence;

use App\Application\Service\Transformer\InputTransformer;
use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InvoiceRecurrenceInput
{
    public function __construct(
        #[Assert\Choice(callback: [RecurrenceFrequency::class, 'frequencies'])]
        #[Map(transform: [InputTransformer::class, 'recurrenceFrequency'])]
        private(set) string $frequency,

        #[Assert\Positive]
        private(set) int $interval,

        #[Assert\NotBlank]
        #[Map(transform: [InputTransformer::class, 'dateOptional'])]
        private(set) string $anchorDate,

        #[Assert\Choice(callback: [RecurrenceEndStrategy::class, 'endStrategies'])]
        #[Map(transform: [InputTransformer::class, 'recurrenceEndStrategy'])]
        private(set) string $endStrategy = RecurrenceEndStrategy::NEVER->value,

        #[Map(transform: [InputTransformer::class, 'dateOptional'])]
        private(set) ?string $endDate = null,

        private(set) ?int $occurrenceCount = null,
    ) {
    }
}
