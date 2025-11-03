<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckGenerator;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class PostgreSQLCheckGeneratorEnumTest extends TestCase
{
    private PostgreSQLCheckGenerator $generator;

    protected function setUp(): void
    {
        $platform = new PostgreSQLCheckAwarePlatform();
        $this->generator = new PostgreSQLCheckGenerator($platform);
    }

    public function test_build_expression_sql_handles_string_enum_values(): void
    {
        $spec = new EnumCheckSpec('CHK_ENUM', 'status', ['draft', 'issued'], true);

        static::assertSame(
            '"status" = ANY(ARRAY[\'draft\'::text, \'issued\'::text])',
            $this->generator->buildExpressionSQL($spec)
        );
    }

    public function test_build_expression_sql_handles_int_enum_values(): void
    {
        $spec = new EnumCheckSpec('CHK_ENUM_INT', 'priority', [0, 1], false);

        static::assertSame(
            '"priority" = ANY(ARRAY[0, 1])',
            $this->generator->buildExpressionSQL($spec)
        );
    }
}
