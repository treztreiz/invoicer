<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceRecurrenceInput
{
    public function __construct(
        #[Groups(['invoice:recurrence'])]
        #[Assert\Choice(callback: [self::class, 'frequencies'])]
        public string $frequency,

        #[Groups(['invoice:recurrence'])]
        #[Assert\Positive]
        public int $interval,

        #[Groups(['invoice:recurrence'])]
        #[Assert\NotBlank]
        public string $anchorDate,

        #[Groups(['invoice:recurrence'])]
        #[Assert\Choice(callback: [self::class, 'endStrategies'])]
        public string $endStrategy = RecurrenceEndStrategy::UNTIL_DATE->value,

        #[Groups(['invoice:recurrence'])]
        public ?string $endDate = null,

        #[Groups(['invoice:recurrence'])]
        public ?int $occurrenceCount = null,
    ) {
    }

    /** @return list<string> */
    public static function frequencies(): array
    {
        return array_map(static fn (RecurrenceFrequency $frequency) => $frequency->value, RecurrenceFrequency::cases());
    }

    /** @return list<string> */
    public static function endStrategies(): array
    {
        return array_map(static fn (RecurrenceEndStrategy $strategy) => $strategy->value, RecurrenceEndStrategy::cases());
    }
}
