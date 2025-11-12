<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;

final readonly class InvoiceRecurrencePayload
{
    public function __construct(
        public RecurrenceFrequency $frequency,
        public int $interval,
        public \DateTimeImmutable $anchorDate,
        public RecurrenceEndStrategy $endStrategy,
        public ?\DateTimeImmutable $endDate,
        public ?int $occurrenceCount,
    ) {
    }
}
