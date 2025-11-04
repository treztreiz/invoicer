<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckGenerator;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class PostgreSQLCheckGeneratorTest extends TestCase
{
    private PostgreSQLCheckGenerator $generator;

    protected function setUp(): void
    {
        $platform = new PostgreSQLCheckAwarePlatform();
        $this->generator = new PostgreSQLCheckGenerator($platform);
    }

    // SOFT XOR CHECK ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function test_build_expression_sql_quotes_identifiers(): void
    {
        $spec = new SoftXorCheckSpec('CHK', ['recurrence_id', 'installment_plan_id']);

        $expression = $this->generator->buildExpressionSQL($spec);

        static::assertSame('num_nonnulls("recurrence_id", "installment_plan_id") <= 1', $expression);
    }

    public function test_build_add_check_sql_includes_guard_clause(): void
    {
        $spec = new SoftXorCheckSpec('CHK_INV', ['col_a', 'col_b']);
        $sql = $this->generator->buildAddCheckSQL('"invoice"', $spec);

        static::assertStringContainsString('DO $$', $sql);
        static::assertStringContainsString('IF NOT EXISTS', $sql);
        static::assertStringContainsString('ALTER TABLE "invoice" ADD CONSTRAINT "CHK_INV" CHECK (', $sql);
        static::assertStringContainsString('CHECK (num_nonnulls("col_a", "col_b") <= 1)', $sql);
    }

    public function test_build_drop_check_sql_uses_if_exists(): void
    {
        $sql = $this->generator->buildDropCheckSQL('"invoice"', new SoftXorCheckSpec('CHK_INV', ['a', 'b']));

        static::assertSame('ALTER TABLE "invoice" DROP CONSTRAINT IF EXISTS "CHK_INV"', $sql);
    }

    // ENUM CHECK //////////////////////////////////////////////////////////////////////////////////////////////////////

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
