<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class VatRateTest extends TestCase
{
    public function test_accepts_non_negative_percentage(): void
    {
        $vat = new VatRate('20.00');

        static::assertSame('20.00', $vat->value);
    }

    public function test_rejects_negative_percentage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VatRate('-5.00');
    }

    public function test_rejects_more_than_two_decimals(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VatRate('5.123');
    }
}
