<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class MoneyTest extends TestCase
{
    public function test_accepts_numeric_string_with_two_decimals(): void
    {
        $money = new Money('123.45');

        static::assertSame('123.45', $money->value);
    }

    public function test_rejects_more_than_two_decimals(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Money('10.123');
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Money('-5.00');
    }
}
