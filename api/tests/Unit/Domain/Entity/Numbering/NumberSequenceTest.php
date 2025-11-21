<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Numbering;

use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use App\Domain\Exception\DomainGuardException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class NumberSequenceTest extends TestCase
{
    #[DataProvider('documentTypesProvider')]
    public function test_reserve_next_returns_current_and_increments(DocumentType $documentType): void
    {
        $sequence = new NumberSequence($documentType, 2026);

        static::assertSame(1, $sequence->reserveNext());
        static::assertSame(2, $sequence->nextValue);

        static::assertSame(2, $sequence->reserveNext());
        static::assertSame(3, $sequence->nextValue);
    }

    #[DataProvider('documentTypesProvider')]
    public function test_negative_year_is_rejected(DocumentType $documentType): void
    {
        static::expectException(DomainGuardException::class);
        new NumberSequence($documentType, -1);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function documentTypesProvider(): iterable
    {
        yield 'Quote' => [DocumentType::QUOTE];
        yield 'Invoice' => [DocumentType::INVOICE];
    }
}
