<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;

final readonly class InstallmentPayload
{
    public function __construct(
        private(set) int $position,
        private(set) string $percentage,
        private(set) AmountBreakdown $amount,
        private(set) ?\DateTimeImmutable $dueDate,
    ) {
    }
}
