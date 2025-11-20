<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document;

use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use Symfony\Component\Uid\Uuid;

final readonly class DocumentLinePayload
{
    public function __construct(
        private(set) ?Uuid $id,
        private(set) string $description,
        private(set) Quantity $quantity,
        private(set) RateUnit $rateUnit,
        private(set) Money $rate,
    ) {
    }
}
