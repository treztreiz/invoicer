<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Application\Dto\Document\Input\DocumentLineInput;
use App\Domain\Enum\RateUnit;
use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\Service\MoneyMath;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

final readonly class DocumentLineFactory
{
    /**
     * @param numeric-string $vatRate
     */
    public function create(DocumentLineInput $input, string $vatRate, int $position): DocumentLinePayload
    {
        return $this->buildPayload($input, $vatRate, $position);
    }

    /**
     * @param numeric-string $vatRate
     */
    private function buildPayload(DocumentLineInput $line, string $vatRate, int $position): DocumentLinePayload
    {
        $quantity = new Quantity(MoneyMath::decimal($line->quantity, 3));
        $rate = new Money(MoneyMath::decimal($line->rate));

        $net = MoneyMath::multiply($quantity->value, $rate->value);
        $tax = MoneyMath::percentage($net, $vatRate);
        $gross = MoneyMath::add($net, $tax);

        return new DocumentLinePayload(
            description: $line->description,
            quantity: $quantity,
            rateUnit: RateUnit::from($line->rateUnit),
            rate: $rate,
            amount: AmountBreakdown::fromValues($net, $tax, $gross),
            position: $position,
            lineId: $line->lineId
        );
    }
}
