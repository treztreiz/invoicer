<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\DomainGuardException;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class AmountBreakdownTest extends TestCase
{
    public function test_instantiation_succeeds_when_totals_match(): void
    {
        $breakdown = new AmountBreakdown(
            new Money('100.00'),
            new Money('20.00'),
            new Money('120.00'),
        );

        static::assertSame('100.00', $breakdown->net->value);
        static::assertSame('20.00', $breakdown->tax->value);
        static::assertSame('120.00', $breakdown->gross->value);
    }

    public function test_instantiation_fails_when_totals_do_not_match(): void
    {
        static::expectException(DomainGuardException::class);
        static::expectExceptionMessage('Gross amount must equal net plus tax.');

        new AmountBreakdown(
            new Money('100.00'),
            new Money('20.00'),
            new Money('119.99'),
        );
    }

    public function test_from_values_factory_builds_breakdown(): void
    {
        $breakdown = AmountBreakdown::fromValues('50.00', '10.00', '60.00');

        static::assertSame('50.00', $breakdown->net->value);
        static::assertSame('10.00', $breakdown->tax->value);
        static::assertSame('60.00', $breakdown->gross->value);
    }
}
