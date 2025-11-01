<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\Migrations;

use App\Infrastructure\Doctrine\Migrations\PostgreSqlSchemaFixSqlGenerator;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL120Platform;
use Doctrine\Migrations\Configuration\Configuration;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class PostgreSqlSchemaFixSqlGeneratorTest extends TestCase
{
    public function test_create_schema_public_is_removed_for_postgres(): void
    {
        $platform = new PostgreSQL120Platform();
        $generator = new PostgreSqlSchemaFixSqlGenerator(new Configuration(), $platform);

        $sql = [
            'CREATE SCHEMA public',
            'ALTER TABLE invoice ADD COLUMN foo INT',
        ];

        $rendered = $generator->generate($sql);

        static::assertStringNotContainsString('CREATE SCHEMA public', $rendered);
        static::assertStringContainsString('ALTER TABLE invoice ADD COLUMN foo INT', $rendered);
    }

    public function test_create_schema_public_is_kept_for_non_postgres_platform(): void
    {
        $platform = new MySQLPlatform();
        $generator = new PostgreSqlSchemaFixSqlGenerator(new Configuration(), $platform);

        $sql = [
            'CREATE SCHEMA public',
            'ALTER TABLE invoice ADD COLUMN foo INT',
        ];

        $rendered = $generator->generate($sql);

        static::assertStringContainsString('CREATE SCHEMA public', $rendered);
        static::assertStringContainsString('ALTER TABLE invoice ADD COLUMN foo INT', $rendered);
    }

    public function test_leading_create_schema_is_removed_even_with_extra_spacing(): void
    {
        $platform = new PostgreSQL120Platform();
        $generator = new PostgreSqlSchemaFixSqlGenerator(new Configuration(), $platform);

        $sql = [
            '   create   schema   PUBLIC   ',
            'ALTER TABLE invoice ADD COLUMN foo INT',
        ];

        $rendered = $generator->generate($sql);

        static::assertStringNotContainsString('CREATE SCHEMA public', strtoupper($rendered));
        static::assertStringContainsString('ALTER TABLE invoice ADD COLUMN foo INT', $rendered);
    }
}
