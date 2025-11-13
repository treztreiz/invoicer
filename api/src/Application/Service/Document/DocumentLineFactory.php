<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Application\Service\MoneyMath;
use App\Application\UseCase\Document\Input\DocumentLineInput;
use App\Domain\DTO\DocumentLinePayload;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

final readonly class DocumentLineFactory
{
    /**
     * @param array<string, mixed>|DocumentLineInput $input
     * @param numeric-string                         $vatRate
     */
    public function create(array|DocumentLineInput $input, string $vatRate, int $position): DocumentLinePayload
    {
        return $this->buildPayload($this->normalizeInput($input), $vatRate, $position);
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

    /** @param array<string, mixed>|DocumentLineInput $input */
    private function normalizeInput(array|DocumentLineInput $input): DocumentLineInput
    {
        if ($input instanceof DocumentLineInput) {
            return $input;
        }

        return new DocumentLineInput(
            description: (string) ($input['description'] ?? ''),
            quantity: (float) ($input['quantity'] ?? 0),
            rateUnit: (string) ($input['rateUnit'] ?? RateUnit::HOURLY->value),
            rate: (float) ($input['rate'] ?? 0),
            lineId: isset($input['lineId']) ? (string) $input['lineId'] : null,
        );
    }
}
