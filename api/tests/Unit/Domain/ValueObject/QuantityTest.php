<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\DomainGuardException;
use App\Domain\ValueObject\Quantity;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class QuantityTest extends TestCase
{
    public function test_accepts_three_decimal_places(): void
    {
        $quantity = new Quantity('12.345');

        static::assertSame('12.345', $quantity->value);
    }

    public function test_rejects_more_than_three_decimals(): void
    {
        $this->expectException(DomainGuardException::class);

        new Quantity('1.2345');
    }

    public function test_rejects_negative_quantity(): void
    {
        $this->expectException(DomainGuardException::class);

        new Quantity('-1.000');
    }
}
