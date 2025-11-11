<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;

final readonly class InstallmentPayload
{
    public function __construct(
        public int $position,
        public string $percentage,
        public AmountBreakdown $amount,
        public ?\DateTimeImmutable $dueDate,
    ) {
    }
}
