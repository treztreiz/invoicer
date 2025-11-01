<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Generator\SqlGenerator;

/**
 * PostgreSQL `CREATE SCHEMA public` fix.
 *
 * @see https://github.com/doctrine/migrations/issues/1415
 */
final class PostgreSqlSchemaFixSqlGenerator extends SqlGenerator
{
    public function __construct(
        Configuration $configuration,
        private readonly AbstractPlatform $platform,
    ) {
        parent::__construct($configuration, $platform);
    }

    public function generate(
        array $sql,
        bool $formatted = false,
        ?bool $nowdocOutput = null,
        int $lineLength = 120,
        bool $checkDbPlatform = true,
    ): string {
        if (!empty($sql)) {
            $sql = $this->filterPublicSchemaStatements($sql);
        }

        return parent::generate($sql, $formatted, $nowdocOutput, $lineLength, $checkDbPlatform);
    }

    /**
     * @param non-empty-array<string> $sql
     *
     * @return non-empty-array<string>|list<string>
     */
    private function filterPublicSchemaStatements(array $sql): array
    {
        // Doctrine DBAL's PostgreSQL diff generator emits a bogus CREATE SCHEMA public
        // entry whenever the schema is already present. We drop that statement so
        // generated migrations remain idempotent.
        if (!$this->isPostgreSQLPlatform()) {
            return $sql;
        }

        $first = $sql[0];
        $normalized = strtoupper(trim($first));

        if ('CREATE SCHEMA PUBLIC' === $normalized) {
            array_shift($sql);
        }

        return $sql;
    }

    private function isPostgreSQLPlatform(): bool
    {
        return $this->platform instanceof PostgreSQLPlatform
            || is_subclass_of($this->platform, PostgreSQLPlatform::class);
    }
}
