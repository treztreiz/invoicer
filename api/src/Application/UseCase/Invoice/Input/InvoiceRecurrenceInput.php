<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InvoiceRecurrenceInput
{
    public function __construct(
        #[Groups(['invoice:recurrence'])]
        #[Assert\Choice(callback: [RecurrenceFrequency::class, 'frequencies'])]
        private(set) string $frequency,

        #[Groups(['invoice:recurrence'])]
        #[Assert\Positive]
        private(set) int $interval,

        #[Groups(['invoice:recurrence'])]
        #[Assert\NotBlank]
        private(set) string $anchorDate,

        #[Groups(['invoice:recurrence'])]
        #[Assert\Choice(callback: [RecurrenceEndStrategy::class, 'endStrategies'])]
        private(set) string $endStrategy = RecurrenceEndStrategy::UNTIL_DATE->value,

        #[Groups(['invoice:recurrence'])]
        private(set) ?string $endDate = null,

        #[Groups(['invoice:recurrence'])]
        private(set) ?int $occurrenceCount = null,
    ) {
    }
}
