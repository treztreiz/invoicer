<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Application\Service\MoneyMath;
use App\Application\UseCase\Document\Input\DocumentLineInput;
use App\Domain\DTO\DocumentLinePayloadCollection;
use App\Domain\ValueObject\AmountBreakdown;

final readonly class DocumentLinePayloadFactory
{
    public function __construct(private DocumentLineFactory $lineFactory)
    {
    }

    /**
     * @param list<DocumentLineInput|array<string, mixed>> $lines
     * @param numeric-string                               $vatRate
     */
    public function build(array $lines, string $vatRate): DocumentLinePayloadCollection
    {
        $linePayloads = [];
        $totalNet = '0.00';
        $totalTax = '0.00';

        foreach ($lines as $index => $lineInput) {
            $linePayload = $this->lineFactory->create($lineInput, $vatRate, $index);
            $linePayloads[] = $linePayload;

            $totalNet = MoneyMath::add($totalNet, $linePayload->amount->net->value);
            $totalTax = MoneyMath::add($totalTax, $linePayload->amount->tax->value);
        }

        $total = AmountBreakdown::fromValues($totalNet, $totalTax, MoneyMath::add($totalNet, $totalTax));

        return new DocumentLinePayloadCollection($linePayloads, $total);
    }
}
