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

        self::assertSame(1, $sequence->reserveNext());
        self::assertSame(2, $sequence->nextValue());

        self::assertSame(2, $sequence->reserveNext());
        self::assertSame(3, $sequence->nextValue());
    }

    public function test_negative_year_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new NumberSequence(DocumentType::QUOTE, -1);
    }
}
