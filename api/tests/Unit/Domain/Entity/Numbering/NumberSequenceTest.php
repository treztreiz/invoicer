<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity\Numbering;

use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use PHPUnit\Framework\TestCase;

final class NumberSequenceTest extends TestCase
{
    public function test_reserve_next_returns_current_and_increments(): void
    {
        $sequence = new NumberSequence(DocumentType::INVOICE, 2026);

        static::assertSame(1, $sequence->reserveNext());
        static::assertSame(2, $sequence->nextValue());

        static::assertSame(2, $sequence->reserveNext());
        static::assertSame(3, $sequence->nextValue());
    }

    public function test_negative_year_is_rejected(): void
    {
        static::expectException(\InvalidArgumentException::class);
        new NumberSequence(DocumentType::QUOTE, -1);
    }
}
