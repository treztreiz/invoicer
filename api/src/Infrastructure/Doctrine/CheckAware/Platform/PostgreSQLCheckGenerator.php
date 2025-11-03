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

    public function buildExpressionSQL(CheckSpecInterface $spec): string
    {
        return match (get_class($spec)) {
            SoftXorCheckSpec::class => $this->buildSoftXor($spec),
            EnumCheckSpec::class => $this->buildEnum($spec),
            default => throw new \InvalidArgumentException('Unsupported check type: '.get_class($spec)),
        };
    }

    private function buildSoftXor(SoftXorCheckSpec $spec): string
    {
        $cols = array_map(fn(string $col) => $this->platform->quoteIdentifier($col), $spec->columns);

        return 'num_nonnulls('.implode(', ', $cols).') <= 1';
    }

    private function buildEnum(EnumCheckSpec $spec): string
    {
        $columnSql = $this->platform->quoteIdentifier($spec->column);

        $valuesSql = array_map(
            static fn(string|int $value): string => $spec->isString ?
                $this->platform->quoteStringLiteral((string)$value).'::text'
                : (string)$value,
            $spec->values
        );

        return sprintf('%s = ANY(ARRAY[%s])', $columnSql, implode(', ', $valuesSql));
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

    private function unquote(string $quotedTable): string
    {
        // "schema"."table" or "table" -> table
        return trim(str_replace('"', '', (string)preg_replace('#^(.+\\.)?"([^"]+)"$#', '$2', $quotedTable)));
    }
}
