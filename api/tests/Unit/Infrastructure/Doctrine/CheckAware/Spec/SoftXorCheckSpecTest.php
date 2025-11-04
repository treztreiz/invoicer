<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class SoftXorCheckSpecTest extends TestCase
{
    private CheckNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CheckNormalizer();
    }

    public function test_valid_spec_is_normalized(): void
    {
        $spec = new SoftXorCheckSpec('chk_soft_xor', ['Recurrence_ID', 'installment_plan_id']);

        $normalized = $spec->normalizeWith($this->normalizer);

        static::assertTrue($normalized->normalized);
        static::assertSame('CHK_SOFT_XOR', $normalized->name);
        static::assertSame(['recurrence_id', 'installment_plan_id'], $normalized->columns);
    }

    public function test_at_least_two_columns_is_required(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SoftXorCheckSpec requires at least two columns.');

        new SoftXorCheckSpec('CHK_INVALID', ['only_one']);
    }

    public function test_normalize_with_is_idempotent(): void
    {
        $spec = new SoftXorCheckSpec('chk_soft_xor', ['col_a', 'col_b']);

        $first = $spec->normalizeWith($this->normalizer);
        $second = $first->normalizeWith($this->normalizer);

        static::assertSame($first, $second);
    }
}
