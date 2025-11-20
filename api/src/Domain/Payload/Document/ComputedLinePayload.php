<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document;

use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use Symfony\Component\Uid\Uuid;

final class ComputedLinePayload
{
    public ?Uuid $id {
        get => $this->payload->id;
    }

    public string $description {
        get => $this->payload->description;
    }

    public Quantity $quantity {
        get => $this->payload->quantity;
    }

    public RateUnit $rateUnit {
        get => $this->payload->rateUnit;
    }

    public Money $rate {
        get => $this->payload->rate;
    }

    public function __construct(
        private(set) readonly DocumentLinePayload $payload,
        private(set) readonly AmountBreakdown $amount,
        private(set) readonly int $position,
    ) {
    }
}
