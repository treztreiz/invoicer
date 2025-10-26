<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

final readonly class DocumentLinePayload
{
    public function __construct(
        public string $description,
        public Quantity $quantity,
        public Money $unitPrice,
        public Money $amountNet,
        public Money $amountTax,
        public Money $amountGross,
        public int $position,
    ) {
    }
}
