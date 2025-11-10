<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\UseCase\Invoice\Input\InvoiceLineInput;
use App\Application\UseCase\Quote\Input\QuoteLineInput;
use App\Domain\DTO\DocumentLinePayload;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;

final readonly class DocumentLineFactory
{
    public function __construct(private MoneyMath $math)
    {
    }

    /**
     * @param array<string, mixed>|QuoteLineInput $input
     */
    public function fromQuoteInput(array|QuoteLineInput $input, string $vatRate, int $position): DocumentLinePayload
    {
        $line = $input instanceof QuoteLineInput ? $input : $this->hydrateQuoteInput($input);

        return $this->buildPayload($line, $vatRate, $position);
    }

    /**
     * @param array<string, mixed>|InvoiceLineInput $input
     */
    public function fromInvoiceInput(array|InvoiceLineInput $input, string $vatRate, int $position): DocumentLinePayload
    {
        $line = $input instanceof InvoiceLineInput ? $input : $this->hydrateInvoiceInput($input);

        return $this->buildPayload($line, $vatRate, $position);
    }

    private function buildPayload(QuoteLineInput|InvoiceLineInput $line, string $vatRate, int $position): DocumentLinePayload
    {
        $quantity = new Quantity($this->math->decimal($line->quantity, 3));
        $rate = new Money($this->math->decimal($line->rate));

        $net = $this->math->multiply($quantity->value, $rate->value);
        $tax = $this->math->percentage($net, $vatRate);
        $gross = $this->math->add($net, $tax);

        return new DocumentLinePayload(
            description: $line->description,
            quantity: $quantity,
            rateUnit: RateUnit::from($line->rateUnit),
            rate: $rate,
            amount: new AmountBreakdown(
                net: new Money($net),
                tax: new Money($tax),
                gross: new Money($gross),
            ),
            position: $position,
        );
    }

    /**
     * @param array<string, mixed> $line
     */
    private function hydrateQuoteInput(array $line): QuoteLineInput
    {
        return new QuoteLineInput(
            description: (string) ($line['description'] ?? ''),
            quantity: (float) ($line['quantity'] ?? 0),
            rateUnit: (string) ($line['rateUnit'] ?? RateUnit::HOURLY->value),
            rate: (float) ($line['rate'] ?? 0),
        );
    }

    /**
     * @param array<string, mixed> $line
     */
    private function hydrateInvoiceInput(array $line): InvoiceLineInput
    {
        return new InvoiceLineInput(
            description: (string) ($line['description'] ?? ''),
            quantity: (float) ($line['quantity'] ?? 0),
            rateUnit: (string) ($line['rateUnit'] ?? RateUnit::HOURLY->value),
            rate: (float) ($line['rate'] ?? 0),
        );
    }
}
