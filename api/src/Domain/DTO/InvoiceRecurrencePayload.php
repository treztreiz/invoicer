<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\RecurrenceEndStrategy;
use App\Domain\Enum\RecurrenceFrequency;

final readonly class InvoiceRecurrencePayload
{
    public function __construct(
        private(set) RecurrenceFrequency $frequency,
        private(set) int $interval,
        private(set) \DateTimeImmutable $anchorDate,
        private(set) RecurrenceEndStrategy $endStrategy,
        private(set) ?\DateTimeImmutable $endDate,
        private(set) ?int $occurrenceCount,
    ) {
    }
}
