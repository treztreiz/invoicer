<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Platform;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckSpecInterface;
use App\Infrastructure\Persistence\Doctrine\ValueObject\SoftXorCheckSpec;

final readonly class PostgreSQLCheckGenerator implements CheckGeneratorInterface
{
    public function __construct(
        private(set) PostgreSQLCheckPlatform $platform,
    ) {
    }

    // CHECKS EXPRESSIONS //////////////////////////////////////////////////////////////////////////////////////////////

    private function buildSoftXor(SoftXorCheckSpec $spec): string
    {
        $payload = $spec->expr;
        $cols = array_map(fn (string $col) => $this->platform->quoteIdentifier($col), $payload['cols']);

        return 'num_nonnulls('.implode(', ', $cols).') <= 1';
    }

    public function buildExpressionSql(CheckSpecInterface $spec): string
    {
        return match (get_class($spec)) {
            SoftXorCheckSpec::class => $this->buildSoftXor($spec),
            default => throw new \InvalidArgumentException('Unsupported check type: '.get_class($spec)),
        };
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function buildAddCheckSql(string $tableNameSql, CheckSpecInterface $spec): string
    {
        $expr = $this->buildExpressionSql($spec);
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
  EXECUTE 'ALTER TABLE $tableNameSql ADD CONSTRAINT "$name" CHECK ($expr)';
END IF;
END$$
SQL;
    }

    public function buildDropCheckSql(string $tableNameSql, CheckSpecInterface $spec): string
    {
        return 'ALTER TABLE '.$tableNameSql.' DROP CONSTRAINT IF EXISTS "'.$spec->name.'"';
    }

    public function buildIntrospectionSql(): string
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
            'table' => (string) $row['table_name'],
            'name' => (string) $row['name'],
            'expr' => (string) $row['def'],
        ];
    }

    // HELPERS /////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function normalizeExpressionSql(string $expr): string
    {
        // 1) trim
        $expr = trim($expr);

        // 2) strip leading CHECK (...) wrapper if present
        //    pg_get_constraintdef returns e.g. "CHECK ((num_nonnulls(...)))"
        if (preg_match('/^check\s*\((.*)\)$/is', $expr, $m)) {
            $expr = $m[1];
        }

        // 3) remove redundant outer parentheses repeatedly: ((X)) -> X
        $expr = $this->stripOuterParens($expr);

        // 4) remove identifier quotes (") – safe because strings use single quotes in PG
        $expr = str_replace('"', '', $expr);

        // 5) collapse whitespace and spaces around commas
        $expr = preg_replace('/\s+/', ' ', $expr) ?? $expr;
        $expr = preg_replace('/\s*,\s*/', ',', $expr) ?? $expr;

        // 6) lowercase everything (functions, identifiers); numbers/strings unaffected
        $expr = strtolower($expr);

        // Done: a canonical, comparable form
        return trim($expr);
    }

    private function stripOuterParens(string $s): string
    {
        // remove a single pair of outer parens if they wrap the whole expression
        while ($this->wrappedByParens($s)) {
            $s = substr($s, 1, -1);
            $s = trim($s);
        }

        return $s;
    }

    private function wrappedByParens(string $s): bool
    {
        $s = trim($s);
        if ('' === $s || '(' !== $s[0] || !str_ends_with($s, ')')) {
            return false;
        }
        $depth = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; ++$i) {
            $ch = $s[$i];
            if ('(' === $ch) {
                ++$depth;
            } elseif (')' === $ch) {
                --$depth;
                if (0 === $depth && $i !== $len - 1) {
                    // outer paren closes before the end => not a full wrapper
                    return false;
                }
            }
        }

        return true;
    }

    private function unquote(string $quotedTable): string
    {
        // "schema"."table" or "table" -> table
        return trim(str_replace('"', '', (string) preg_replace('#^(.+\\.)?"([^"]+)"$#', '$2', $quotedTable)));
    }
}
