<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

final readonly class DocumentLinePayload
{
    public function __construct(
        public string $description,
        public Quantity $quantity,
        public RateUnit $rateUnit,
        public Money $rate,
        public AmountBreakdown $amount,
        public int $position,
    ) {
    }
}
