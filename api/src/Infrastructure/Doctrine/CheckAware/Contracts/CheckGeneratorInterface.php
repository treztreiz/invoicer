<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Contracts;

use Doctrine\DBAL\Platforms\AbstractPlatform;

interface CheckGeneratorInterface
{
    public AbstractPlatform $platform {
        get;
    }

    /** Single SQL statement, idempotent, to ADD the given check on a table. */
    public function buildAddCheckSQL(string $tableNameSql, CheckSpecInterface $spec): string;

    /** Single SQL statement (prefer IF EXISTS) to DROP a check by name. */
    public function buildDropCheckSQL(string $tableNameSql, CheckSpecInterface $spec): string;

    /** Introspection: return SQL text to list all checks in current schema. */
    public function buildIntrospectionSQL(): string;

    /**
     * @param array<string, mixed> $row
     *
     * @return array{table: string, name: string, expr: string}
     */
    public function mapIntrospectionRow(array $row): array;

    /**
     * Build the **normalized** expression SQL for comparison from a concrete spec.
     */
    public function buildExpressionSQL(CheckSpecInterface $spec): string;
}
