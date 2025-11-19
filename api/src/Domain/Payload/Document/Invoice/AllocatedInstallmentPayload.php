<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document\Invoice;

use App\Domain\ValueObject\AmountBreakdown;

final readonly class AllocatedInstallmentPayload
{
    public function __construct(
        private(set) int $position,
        private(set) string $percentage,
        private(set) AmountBreakdown $amount,
        private(set) ?\DateTimeImmutable $dueDate,
    ) {
    }
}
