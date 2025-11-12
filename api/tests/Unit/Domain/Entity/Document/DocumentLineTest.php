<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Document\Document;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class DocumentLineTest extends TestCase
{
    #[DataProvider('documentsProvider')]
    public function test_construct_stores_values(Document $document): void
    {
        $line = new DocumentLine(
            document: $document,
            description: 'Development work',
            quantity: new Quantity('10'),
            rateUnit: RateUnit::HOURLY,
            rate: new Money('100'),
            amount: new AmountBreakdown(
                net: new Money('1000'),
                tax: new Money('200'),
                gross: new Money('1200'),
            ),
            position: 1
        );

        static::assertSame($document, $line->document);
        static::assertSame(1, $line->position);
    }

    #[DataProvider('documentsProvider')]
    public function test_blank_description_is_rejected(Document $document): void
    {
        static::expectException(\InvalidArgumentException::class);

        new DocumentLine(
            document: $document,
            description: '   ',
            quantity: new Quantity('1'),
            rateUnit: RateUnit::HOURLY,
            rate: new Money('10'),
            amount: new AmountBreakdown(
                net: new Money('10'),
                tax: new Money('2'),
                gross: new Money('12'),
            ),
            position: 0
        );
    }

    #[DataProvider('documentsProvider')]
    public function test_negative_position_is_rejected(Document $document): void
    {
        static::expectException(\InvalidArgumentException::class);

        new DocumentLine(
            document: $document,
            description: 'Item',
            quantity: new Quantity('1'),
            rateUnit: RateUnit::DAILY,
            rate: new Money('10'),
            amount: new AmountBreakdown(
                net: new Money('10'),
                tax: new Money('2'),
                gross: new Money('12'),
            ),
            position: -1
        );
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function documentsProvider(): iterable
    {
        yield 'Quote' => [QuoteTest::createQuote()];
        yield 'Invoice' => [InvoiceTest::createInvoice()];
    }
}
