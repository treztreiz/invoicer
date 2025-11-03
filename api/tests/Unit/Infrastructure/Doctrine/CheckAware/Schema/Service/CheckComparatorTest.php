<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckGenerator;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckAwareSchemaManagerFactory;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckComparator;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use App\Infrastructure\Doctrine\CheckAware\Spec\EnumCheckSpec;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class CheckComparatorTest extends TestCase
{
    private CheckRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CheckRegistry(new CheckNormalizer());
    }

    /**
     * @throws SchemaException
     */
    public function test_manually_wired_comparator_exposes_added_modified_and_dropped_checks(): void
    {
        $comparator = $this->createComparator();

        $from = $this->schemaWithExistingChecks([
            'CHK_DROPPED' => 'num_nonnulls(col_a,col_b) <= 1',
            'CHK_MODIFIED' => 'num_nonnulls(col_a,col_b) <= 2',
        ]);
        $to = $this->schemaWithDeclaredSpecs([
            new SoftXorCheckSpec('CHK_MODIFIED', ['columns' => ['col_a', 'col_b']]),
            new SoftXorCheckSpec('CHK_ADDED', ['columns' => ['col_c', 'col_d']]),
        ]);

        $diff = $comparator->compareSchemas($from, $to);

        static::assertCount(1, $diff->getAlteredTables());

        $tableDiff = $diff->getAlteredTables()[0];
        static::assertInstanceOf(CheckAwareTableDiff::class, $tableDiff);

        static::assertSame(
            ['CHK_ADDED'],
            array_map(static fn ($spec) => $spec->name, $tableDiff->getAddedChecks())
        );
        static::assertSame(
            ['CHK_MODIFIED'],
            array_map(static fn ($spec) => $spec->name, $tableDiff->getModifiedChecks())
        );
        static::assertSame(
            ['CHK_DROPPED'],
            array_map(static fn ($spec) => $spec->name, $tableDiff->getDroppedChecks())
        );
    }

    /**
     * @throws SchemaException
     */
    public function test_existing_checks_without_desired_are_dropped(): void
    {
        $from = $this->schemaWithExistingChecks(['CHK_ONE' => 'expr_one']);
        $to = $this->emptySchema();

        $diff = $this->compare($from, $to);

        static::assertCount(1, $diff->getAlteredTables());
        /** @var CheckAwareTableDiff $tableDiff */
        $tableDiff = $diff->getAlteredTables()[0];
        static::assertCount(1, $tableDiff->getDroppedChecks());
        static::assertSame('CHK_ONE', $tableDiff->getDroppedChecks()[0]->name);
    }

    /**
     * @throws SchemaException
     */
    public function test_changed_expression_is_marked_modified(): void
    {
        $from = $this->schemaWithExistingChecks(['CHK_ONE' => 'num_nonnulls(col_a,col_b) <= 2']);
        $to = $this->schemaWithDeclaredSpecs([
            new SoftXorCheckSpec('CHK_ONE', ['columns' => ['col_a', 'col_b']]),
        ]);

        $diff = $this->compare($from, $to);

        /** @var CheckAwareTableDiff $tableDiff */
        $tableDiff = $diff->getAlteredTables()[0];
        static::assertCount(1, $tableDiff->getModifiedChecks());
    }

    /**
     * @throws SchemaException
     */
    public function test_added_spec_is_marked_added(): void
    {
        $from = $this->emptySchema();
        $to = $this->schemaWithDeclaredSpecs([
            new SoftXorCheckSpec('CHK_ADD', ['columns' => ['col_a', 'col_b']]),
        ]);

        $diff = $this->compare($from, $to);

        /** @var CheckAwareTableDiff $tableDiff */
        $tableDiff = $diff->getAlteredTables()[0];
        static::assertCount(1, $tableDiff->getAddedChecks());
        static::assertSame('CHK_ADD', $tableDiff->getAddedChecks()[0]->name);
    }

    /**
     * @throws SchemaException
     */
    public function test_identical_soft_xor_checks_produce_no_diff(): void
    {
        $spec = new SoftXorCheckSpec('CHK_SAME', ['columns' => ['col_a', 'col_b']]);

        $from = $this->schemaWithExistingChecks(['CHK_SAME' => 'num_nonnulls(col_a,col_b) <= 1']);
        $to = $this->schemaWithDeclaredSpecs([$spec]);

        $diff = $this->compare($from, $to);

        static::assertEmpty($diff->getAlteredTables());
    }

    /**
     * @throws SchemaException
     */
    public function test_identical_enum_checks_produce_no_diff(): void
    {
        $spec = new EnumCheckSpec('CHK_ENUM', [
            'column' => 'status',
            'values' => ['draft', 'issued'],
            'is_string' => true,
        ]);

        $existingExpr = 'CHECK ("status" = ANY(ARRAY[\'draft\'::text, \'issued\'::text]))';

        $from = $this->schemaWithExistingChecks(['CHK_ENUM' => $existingExpr]);
        $to = $this->schemaWithDeclaredSpecs([$spec]);

        $diff = $this->compare($from, $to);

        static::assertEmpty($diff->getAlteredTables());
    }

    /**
     * @throws SchemaException
     */
    private function compare(Schema $from, Schema $to): SchemaDiff
    {
        return $this->createComparator()->compareSchemas($from, $to);
    }

    private function createComparator(): CheckComparator
    {
        $platform = new PostgreSQLCheckAwarePlatform();
        $platform->setSchemaManagerFactory(new CheckAwareSchemaManagerFactory());
        $platform->setCheckRegistry($this->registry);
        $platform->setCheckGenerator(new PostgreSQLCheckGenerator($platform));

        return new CheckComparator(new Comparator(), $platform);
    }

    /**
     * @throws SchemaException
     */
    private function emptySchema(): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable('invoice');
        $table->addColumn('id', 'integer');

        return $schema;
    }

    /**
     * @throws SchemaException
     */
    private function schemaWithExistingChecks(array $checks): Schema
    {
        $schema = $this->emptySchema();
        $table = $schema->getTable('invoice');

        $table->addOption('app_checks_present', $checks);

        return $schema;
    }

    /**
     * @throws SchemaException
     */
    private function schemaWithDeclaredSpecs(array $specs): Schema
    {
        $schema = $this->emptySchema();
        $table = $schema->getTable('invoice');

        foreach ($specs as $spec) {
            $this->registry->appendDeclaredSpec($table, $spec);
        }

        return $schema;
    }
}
