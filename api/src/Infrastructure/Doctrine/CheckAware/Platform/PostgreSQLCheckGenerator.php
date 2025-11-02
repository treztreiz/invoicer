<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;

final readonly class PostgreSQLCheckGenerator implements CheckGeneratorInterface
{
    public function __construct(
        private(set) PostgreSQLCheckAwarePlatform $platform,
    ) {
    }

    // CHECKS EXPRESSIONS //////////////////////////////////////////////////////////////////////////////////////////////

    private function buildSoftXor(SoftXorCheckSpec $spec): string
    {
        $payload = $spec->expr;
        $cols = array_map(fn(string $col) => $this->platform->quoteIdentifier($col), $payload['cols']);

        return 'num_nonnulls('.implode(', ', $cols).') <= 1';
    }

    private function buildEnum(EnumCheckSpec $spec): string
    {
        /** @var array{column: string, values: list<string|int>, is_string: bool} $payload */
        $payload = $spec->expr;
        $columnSql = $this->platform->quoteIdentifier($payload['column']);

        if ($payload['is_string']) {
            $valuesSql = array_map(
                fn(string|int $value): string => $this->platform->quoteStringLiteral((string) $value).'::text',
                $payload['values']
            );

            return sprintf('%s = ANY(ARRAY[%s])', $columnSql, implode(', ', $valuesSql));
        }

        $valuesSql = array_map(
            static fn(string|int $value): string => (string) $value,
            $payload['values']
        );

        return sprintf('%s = ANY(ARRAY[%s])', $columnSql, implode(', ', $valuesSql));
    }

    public function buildExpressionSQL(CheckSpecInterface $spec): string
    {
        return match (get_class($spec)) {
            SoftXorCheckSpec::class => $this->buildSoftXor($spec),
            EnumCheckSpec::class => $this->buildEnum($spec),
            default => throw new \InvalidArgumentException('Unsupported check type: '.get_class($spec)),
        };
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function buildAddCheckSQL(string $tableNameSql, CheckSpecInterface $spec): string
    {
        $expr = $this->buildExpressionSQL($spec);
        $exprEscaped = str_replace("'", "''", $expr);
        $name = $spec->name;

        return <<<SQL
DO $$
BEGIN
IF NOT EXISTS (
  SELECT 1
  FROM pg_constraint c
  JOIN pg_class r  ON r.oid = c.conrelid
  JOIN pg_namespace n ON n.oid = r.relnamespace
  WHERE c.conname = '$name'
    AND r.relname = {$this->platform->quoteStringLiteral($this->unquote($tableNameSql))}
    AND n.nspname = current_schema()
) THEN
  EXECUTE 'ALTER TABLE $tableNameSql ADD CONSTRAINT "$name" CHECK ($exprEscaped)';
END IF;
END$$
SQL;
    }

    public function buildDropCheckSQL(string $tableNameSql, CheckSpecInterface $spec): string
    {
        return 'ALTER TABLE '.$tableNameSql.' DROP CONSTRAINT IF EXISTS "'.$spec->name.'"';
    }

    public function buildIntrospectionSQL(): string
    {
        return <<<SQL
SELECT
  rel.relname   AS table_name,
  con.conname   AS name,
  PG_GET_CONSTRAINTDEF(con.oid) AS def
FROM pg_constraint con
JOIN pg_class rel ON rel.oid = con.conrelid
JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
WHERE con.contype = 'c' AND nsp.nspname = CURRENT_SCHEMA()
SQL;
    }

    public function mapIntrospectionRow(array $row): array
    {
        return [
            'table' => (string)$row['table_name'],
            'name' => (string)$row['name'],
            'expr' => (string)$row['def'],
        ];
    }

    // HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function normalizeExpressionSQL(string $expr): string
    {
        // 1) trim
        $expr = trim($expr);

        // 2) lowercase everything to simplify comparisons
        $expr = strtolower($expr);

        // 3) drop postgres casts (::text, ::int[], ...)
        $expr = preg_replace('/::[a-z0-9_]+(?:\[\])?/', '', $expr) ?? $expr;

        // 4) remove the leading "check" keyword if present
        $expr = preg_replace('/^check/', '', $expr) ?? $expr;

        // 5) strip characters that are irrelevant for equality checks
        $expr = str_replace([
            '"', "'", ' ', '(', ')', '[', ']',
        ], '', $expr);

        // 6) normalise comma spacing
        $expr = preg_replace('/\s*,\s*/', ',', $expr) ?? $expr;

        // 7) collapse any remaining whitespace
        $expr = preg_replace('/\s+/', '', $expr) ?? $expr;

        // Final canonical form
        return trim($expr);
    }

    private function unquote(string $quotedTable): string
    {
        // "schema"."table" or "table" -> table
        return trim(str_replace('"', '', (string)preg_replace('#^(.+\\.)?"([^"]+)"$#', '$2', $quotedTable)));
    }
}
