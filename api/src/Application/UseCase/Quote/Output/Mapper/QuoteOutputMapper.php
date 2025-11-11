<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Output\Mapper;

use App\Application\UseCase\Document\Output\DocumentLineOutput;
use App\Application\UseCase\Quote\Output\QuoteOutput;
use App\Application\UseCase\Quote\Output\QuoteTotalsOutput;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Quote;

final class QuoteOutputMapper
{
    /**
     * @param list<string> $availableActions
     */
    public function map(Quote $quote, array $availableActions = []): QuoteOutput
    {
        return new QuoteOutput(
            quoteId: $quote->id?->toRfc4122() ?? '',
            title: $quote->title,
            subtitle: $quote->subtitle,
            status: $quote->status->value,
            currency: $quote->currency,
            vatRate: $quote->vatRate->value,
            total: $this->mapTotal($quote),
            lines: $this->mapLines($quote),
            customerSnapshot: $quote->customerSnapshot,
            companySnapshot: $quote->companySnapshot,
            createdAt: $quote->createdAt,
            availableActions: $availableActions,
        );
    }

    private function mapTotal(Quote $quote): QuoteTotalsOutput
    {
        return new QuoteTotalsOutput(
            net: $quote->total->net->value,
            tax: $quote->total->tax->value,
            gross: $quote->total->gross->value,
        );
    }

    /** @return list<DocumentLineOutput> */
    private function mapLines(Quote $quote): array
    {
        return array_values(
            array_map(
                fn (DocumentLine $line) => new DocumentLineOutput(
                    description: $line->description,
                    quantity: $line->quantity->value,
                    rateUnit: $line->rateUnit->value,
                    rate: $line->rate->value,
                    net: $line->amount->net->value,
                    tax: $line->amount->tax->value,
                    gross: $line->amount->gross->value,
                ),
                $quote->lines->toArray()
            )
        );
    }
}
