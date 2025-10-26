<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Entity\Document\Quote;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;

final class DocumentLineTest extends TestCase
{
    private Quote $quote;

    protected function setUp(): void
    {
        $this->quote = $this->createQuote();
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function test_construct_stores_values(): void
    {
        $line = new DocumentLine(
            document: $this->quote,
            description: 'Development work',
            quantity: new Quantity('10'),
            rateUnit: RateUnit::HOURLY,
            rate: new Money('100'),
            amountNet: new Money('1000'),
            amountTax: new Money('200'),
            amountGross: new Money('1200'),
            position: 1
        );

        static::assertSame($this->quote, $line->document);
        static::assertSame(1, $line->position);
    }

    public function test_blank_description_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DocumentLine(
            document: $this->quote,
            description: '   ',
            quantity: new Quantity('1'),
            rateUnit: RateUnit::HOURLY,
            rate: new Money('10'),
            amountNet: new Money('10'),
            amountTax: new Money('2'),
            amountGross: new Money('12'),
            position: 0
        );
    }

    public function test_negative_position_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DocumentLine(
            document: $this->quote,
            description: 'Item',
            quantity: new Quantity('1'),
            rateUnit: RateUnit::DAILY,
            rate: new Money('10'),
            amountNet: new Money('10'),
            amountTax: new Money('2'),
            amountGross: new Money('12'),
            position: -1
        );
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createQuote(): Quote
    {
        return new Quote(
            title: 'Quote',
            currency: 'EUR',
            vatRate: new VatRate('20'),
            subtotalNet: new Money('100'),
            taxTotal: new Money('20'),
            grandTotal: new Money('120'),
            customerSnapshot: ['name' => 'Customer'],
            companySnapshot: ['name' => 'Company']
        );
    }
}
