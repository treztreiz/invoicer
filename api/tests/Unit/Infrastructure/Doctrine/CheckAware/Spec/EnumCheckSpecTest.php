<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class EnumCheckSpecTest extends TestCase
{
    private CheckNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CheckNormalizer();
    }

    public function test_valid_spec_is_normalized(): void
    {
        $spec = new EnumCheckSpec('chk_status', 'STATUS', ['draft', 'issued'], true);
        $normalized = $spec->normalizeWith($this->normalizer);

        static::assertTrue($normalized->normalized);
        static::assertSame('CHK_STATUS', $normalized->name);
        static::assertSame('status', $normalized->column);
        static::assertSame(['draft', 'issued'], $normalized->values);
        static::assertTrue($normalized->isString);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('%s name cannot be empty.', EnumCheckSpec::class));

        new EnumCheckSpec('   ', 'status', ['draft'], true)->normalizeWith($this->normalizer);
    }

    public function test_empty_column_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumCheckSpec column cannot be empty.');

        new EnumCheckSpec('CHK', '', ['draft'], true);
    }

    public function test_values_must_not_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumCheckSpec requires at least one value.');

        new EnumCheckSpec('CHK', 'status', [], true);
    }

    public function test_values_must_be_uniform_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Values list must contain a uniform scalar type.');

        $spec = new EnumCheckSpec('CHK', 'status', ['draft', 1], true);
        $spec->normalizeWith($this->normalizer);
    }

    public function test_is_string_flag_must_match_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EnumCheckSpec values do not match declared backing type.');

        $spec = new EnumCheckSpec('CHK', 'status', ['draft'], false);
        $spec->normalizeWith($this->normalizer);
    }
}
