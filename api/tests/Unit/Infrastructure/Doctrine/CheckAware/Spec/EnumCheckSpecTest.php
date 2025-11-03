<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class EnumCheckSpecTest extends TestCase
{
    public function test_valid_spec_is_accepted(): void
    {
        $spec = new EnumCheckSpec('chk_status', 'status', ['draft', 'issued'], true);

        $normalized = $spec->normalizeWith(new CheckNormalizer());

        static::assertTrue($normalized->isNormalized());
        static::assertSame('CHK_STATUS', $normalized->name);
        static::assertSame('status', $normalized->column);
        static::assertSame(['draft', 'issued'], $normalized->values);
        static::assertTrue($normalized->isString);
        static::assertFalse($normalized->deferrable);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new EnumCheckSpec('   ', 'status', ['draft'], true))->normalizeWith(new CheckNormalizer());
    }

    public function test_empty_column_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', '', ['draft'], true);
    }

    public function test_values_must_not_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', 'status', [], true);
    }

    public function test_values_must_be_uniform_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', 'status', ['draft', 1], true);
    }

    public function test_is_string_flag_must_match_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', 'status', ['draft'], false);
    }
}
