<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckGenerator;
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

    public function test_build_expression_sql_quotes_identifiers(): void
    {
        $spec = new SoftXorCheckSpec('CHK', ['columns' => ['recurrence_id', 'installment_plan_id']]);

        $expression = $this->generator->buildExpressionSQL($spec);

        static::assertSame('num_nonnulls("recurrence_id", "installment_plan_id") <= 1', $expression);
    }

    public function test_build_add_check_sql_is_idempotent(): void
    {
        $spec = new SoftXorCheckSpec('CHK_INV', ['columns' => ['col_a', 'col_b']]);
        $sql = $this->generator->buildAddCheckSQL('"invoice"', $spec);

        static::assertStringContainsString('DO $$', $sql);
        static::assertStringContainsString('IF NOT EXISTS', $sql);
        static::assertStringContainsString('ALTER TABLE "invoice" ADD CONSTRAINT "CHK_INV" CHECK (', $sql);
    }

    public function test_build_drop_check_sql_uses_if_exists(): void
    {
        $sql = $this->generator->buildDropCheckSQL('"invoice"', new SoftXorCheckSpec('CHK_INV', ['columns' => ['a', 'b']]));

        static::assertSame('ALTER TABLE "invoice" DROP CONSTRAINT IF EXISTS "CHK_INV"', $sql);
    }
}
