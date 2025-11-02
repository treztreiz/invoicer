<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class EnumCheckSpecTest extends TestCase
{
    public function test_valid_spec_is_accepted(): void
    {
        $spec = new EnumCheckSpec('CHK_STATUS', [
            'column' => 'status',
            'values' => ['draft', 'issued'],
        ]);

        static::assertSame('CHK_STATUS', $spec->name);
        static::assertSame(
            ['column' => 'status', 'values' => ['draft', 'issued'], 'is_string' => true],
            $spec->expr
        );
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('   ', [
            'column' => 'status',
            'values' => ['draft'],
        ]);
    }

    public function test_empty_column_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', [
            'column' => '',
            'values' => ['draft'],
        ]);
    }

    public function test_values_must_not_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', [
            'column' => 'status',
            'values' => [],
        ]);
    }

    public function test_values_must_be_uniform_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', [
            'column' => 'status',
            'values' => ['draft', 1],
        ]);
    }

    public function test_is_string_flag_must_match_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new EnumCheckSpec('CHK', [
            'column' => 'status',
            'values' => ['draft'],
            'is_string' => false,
        ]);
    }
}
