<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Document;

use App\Domain\Entity\Document\Document;
use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Enum\RateUnit;
use App\Domain\Exception\DomainGuardException;
use App\Domain\Payload\Document\ComputedLinePayload;
use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Tests\Unit\Domain\Entity\Document\Invoice\InvoiceTest;
use App\Tests\Unit\Domain\Entity\Document\Quote\QuoteTest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType solitary-unit
 */
final class DocumentLineTest extends TestCase
{
    use Factories;

    #[DataProvider('documentsProvider')]
    public function test_construct_stores_values(Document $document): void
    {
        $line = $this->createDocumentLine($document, 1);

        static::assertSame($document, $line->document);
        static::assertSame(1, $line->position);
    }

    #[DataProvider('documentsProvider')]
    public function test_blank_description_is_rejected(Document $document): void
    {
        static::expectException(DomainGuardException::class);

        $this->createDocumentLine($document, description: '   ');
    }

    #[DataProvider('documentsProvider')]
    public function test_negative_position_is_rejected(Document $document): void
    {
        static::expectException(DomainGuardException::class);

        $this->createDocumentLine($document, -1);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createDocumentLine(Document $document, int $position = 0, string $description = 'Item'): DocumentLine
    {
        $payload = new ComputedLinePayload(
            payload: new DocumentLinePayload(
                id: null,
                description: $description,
                quantity: new Quantity('1'),
                rateUnit: RateUnit::DAILY,
                rate: new Money('10'),
            ),
            amount: AmountBreakdown::fromValues('10', '2', '12'),
            position: $position
        );

        return DocumentLine::fromPayload($document, $payload);
    }

    public static function documentsProvider(): iterable
    {
        yield 'Quote' => [QuoteTest::createQuote()];
        yield 'Invoice' => [InvoiceTest::createInvoice()];
    }
}
