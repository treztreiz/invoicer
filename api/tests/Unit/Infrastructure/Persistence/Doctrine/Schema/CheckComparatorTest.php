<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Platform\PostgreSQLCheckPlatform;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckAwareTableDiff;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckComparator;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckOptionManager;
use App\Infrastructure\Persistence\Doctrine\ValueObject\SoftXorCheckSpec;
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
    private CheckOptionManager $optionManager;

    protected function setUp(): void
    {
        $this->optionManager = new CheckOptionManager();
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
        $to = $this->schemaWithDesiredChecks([
            new SoftXorCheckSpec('CHK_ONE', ['cols' => ['col_a', 'col_b']]),
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
        $to = $this->schemaWithDesiredChecks([
            new SoftXorCheckSpec('CHK_ADD', ['cols' => ['col_a', 'col_b']]),
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
    public function test_identical_checks_produce_no_diff(): void
    {
        $spec = new SoftXorCheckSpec('CHK_SAME', ['cols' => ['col_a', 'col_b']]);

        $from = $this->schemaWithExistingChecks(['CHK_SAME' => 'num_nonnulls(col_a,col_b) <= 1']);
        $to = $this->schemaWithDesiredChecks([$spec]);

        $diff = $this->compare($from, $to);

        static::assertEmpty($diff->getAlteredTables());
    }

    /**
     * @throws SchemaException
     */
    private function compare(Schema $from, Schema $to): SchemaDiff
    {
        $comparator = new CheckComparator(new Comparator(), new PostgreSQLCheckPlatform($this->optionManager), $this->optionManager);

        return $comparator->compareSchemas($from, $to);
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

        $table->addOption(
            'app_checks_present',
            array_map(
                static fn (string $name, string $expr): array => ['name' => $name, 'expr' => $expr],
                array_keys($checks),
                $checks,
            )
        );

        return $schema;
    }

    /**
     * @throws SchemaException
     */
    private function schemaWithDesiredChecks(array $specs): Schema
    {
        $schema = $this->emptySchema();
        $table = $schema->getTable('invoice');

        foreach ($specs as $spec) {
            $this->optionManager->appendDesired($table, $spec);
        }

        return $schema;
    }
}
