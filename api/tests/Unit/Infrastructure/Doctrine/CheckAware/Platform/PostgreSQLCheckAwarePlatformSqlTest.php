<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckGenerator;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckAwareSchemaManagerFactory;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class PostgreSQLCheckAwarePlatformSqlTest extends TestCase
{
    private PostgreSQLCheckAwarePlatform $platform;

    protected function setUp(): void
    {
        $this->platform = new PostgreSQLCheckAwarePlatform();
        $this->platform->setSchemaManagerFactory(new CheckAwareSchemaManagerFactory());

        $registry = new CheckRegistry(new CheckNormalizer());
        $this->platform->setCheckRegistry($registry);
        $this->platform->setCheckGenerator(new PostgreSQLCheckGenerator($this->platform));
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function test_get_create_table_sql_appends_check_constraint(): void
    {
        $table = new Table('invoice');
        $table->addColumn('id', 'integer');
        $table->addColumn('col_a', 'string');
        $table->addColumn('col_b', 'string');

        $spec = new SoftXorCheckSpec('CHK_SOFT_XOR', ['col_a', 'col_b']);
        $this->platform->registry->appendDeclaredSpec($table, $spec);

        $sql = $this->platform->getCreateTableSQL($table);

        static::assertNotEmpty(
            array_filter(
                $sql,
                static fn (string $statement): bool => str_contains(
                    $statement,
                    'ADD CONSTRAINT "CHK_SOFT_XOR" CHECK (num_nonnulls("col_a", "col_b") <= 1)'
                )
            ),
            'Expected ADD CONSTRAINT statement not found in create table SQL.'
        );
    }

    /**
     * @throws Exception
     * @throws SchemaException
     */
    public function test_get_create_tables_sql_appends_check_constraint(): void
    {
        $table = new Table('invoice');
        $table->addColumn('id', 'integer');
        $table->addColumn('col_a', 'string');
        $table->addColumn('col_b', 'string');

        $spec = new SoftXorCheckSpec('CHK_SOFT_XOR', ['col_a', 'col_b']);
        $this->platform->registry->appendDeclaredSpec($table, $spec);

        $sql = $this->platform->getCreateTablesSQL([$table]);

        static::assertNotEmpty(
            array_filter(
                $sql,
                static fn (string $statement): bool => str_contains(
                    $statement,
                    'ADD CONSTRAINT "CHK_SOFT_XOR" CHECK (num_nonnulls("col_a", "col_b") <= 1)'
                )
            )
        );
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function test_get_alter_table_sql_appends_check_constraint(): void
    {
        $table = new Table('invoice');
        $table->addColumn('id', 'integer');
        $table->addColumn('col_a', 'string');
        $table->addColumn('col_b', 'string');

        $diff = new CheckAwareTableDiff($table);
        $normalizedSpec = new SoftXorCheckSpec('CHK_NEW_XOR', ['col_a', 'col_b'])
            ->normalizeWith(new CheckNormalizer());
        $diff->addAddedChecks([$normalizedSpec]);

        $sql = $this->platform->getAlterTableSQL($diff);

        static::assertNotEmpty(
            array_filter(
                $sql,
                static fn (string $statement): bool => str_contains(
                    $statement,
                    'ADD CONSTRAINT "CHK_NEW_XOR" CHECK (num_nonnulls("col_a", "col_b") <= 1)'
                )
            ),
            'Expected ADD CONSTRAINT statement not found in alter table SQL.'
        );
    }
}
