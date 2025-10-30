<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Platform;

use App\Infrastructure\Persistence\Doctrine\Platform\PostgreSQLCheckGenerator;
use App\Infrastructure\Persistence\Doctrine\Platform\PostgreSQLCheckPlatform;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckOptionManager;
use App\Infrastructure\Persistence\Doctrine\ValueObject\SoftXorCheckSpec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class PostgreSQLCheckGeneratorTest extends TestCase
{
    private PostgreSQLCheckGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new PostgreSQLCheckGenerator(new PostgreSQLCheckPlatform(new CheckOptionManagerStub()));
    }

    public function test_build_expression_sql_quotes_identifiers(): void
    {
        $spec = new SoftXorCheckSpec('CHK', ['cols' => ['recurrence_id', 'installment_plan_id']]);

        $expression = $this->generator->buildExpressionSQL($spec);

        static::assertSame('num_nonnulls("recurrence_id", "installment_plan_id") <= 1', $expression);
    }

    public function test_build_add_check_sql_is_idempotent(): void
    {
        $spec = new SoftXorCheckSpec('CHK_INV', ['cols' => ['col_a', 'col_b']]);
        $sql = $this->generator->buildAddCheckSQL('"invoice"', $spec);

        static::assertStringContainsString('DO $$', $sql);
        static::assertStringContainsString('IF NOT EXISTS', $sql);
        static::assertStringContainsString('ALTER TABLE "invoice" ADD CONSTRAINT "CHK_INV" CHECK (', $sql);
    }

    public function test_build_drop_check_sql_uses_if_exists(): void
    {
        $sql = $this->generator->buildDropCheckSQL('"invoice"', new SoftXorCheckSpec('CHK_INV', ['cols' => ['a', 'b']]));

        static::assertSame('ALTER TABLE "invoice" DROP CONSTRAINT IF EXISTS "CHK_INV"', $sql);
    }

    #[DataProvider('normalizationProvider')]
    public function test_normalize_expression_sql(string $input, string $expected): void
    {
        static::assertSame($expected, $this->generator->normalizeExpressionSQL($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizationProvider(): iterable
    {
        yield 'constraint def' => [
            'CHECK ((NUM_NONNULLS("recurrence_id", "installment_plan_id")) <= 1)',
            'num_nonnulls(recurrence_id,installment_plan_id) <= 1',
        ];
        yield 'already normalized' => [
            'num_nonnulls(col_a,col_b) <= 1',
            'num_nonnulls(col_a,col_b) <= 1',
        ];
    }
}

final class CheckOptionManagerStub extends CheckOptionManager
{
}
