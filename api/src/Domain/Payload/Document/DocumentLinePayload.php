<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document;

use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

final readonly class DocumentLinePayload
{
    public function __construct(
        private(set) string $description,
        private(set) Quantity $quantity,
        private(set) RateUnit $rateUnit,
        private(set) Money $rate,
        private(set) AmountBreakdown $amount,
        private(set) int $position,
        private(set) ?string $lineId = null,
    ) {
    }
}
