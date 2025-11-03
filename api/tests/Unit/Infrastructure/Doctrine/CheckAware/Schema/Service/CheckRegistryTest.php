<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class CheckRegistryTest extends TestCase
{
    private readonly CheckRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CheckRegistry(new CheckNormalizer());
    }

    public function test_declared_specs_are_appended(): void
    {
        $table = new Table('invoice');

        static::assertSame([], $this->registry->getDeclaredSpecs($table));

        $first = new SoftXorCheckSpec('chk_inv_soft_xor', ['Recurrence_ID', 'installment_plan_id']);
        $second = new SoftXorCheckSpec('CHK_inv_another', ['FOO_ID', 'bar_id']);

        $this->registry->appendDeclaredSpec($table, $first);
        $this->registry->appendDeclaredSpec($table, $second);

        /** @var list<SoftXorCheckSpec> $declared */
        $declared = $this->registry->getDeclaredSpecs($table);

        static::assertCount(2, $declared);
        static::assertTrue($declared[0]->normalized);
        static::assertSame('CHK_INV_SOFT_XOR', $declared[0]->name);
        static::assertSame(['recurrence_id', 'installment_plan_id'], $declared[0]->columns);
        static::assertTrue($declared[1]->normalized);
        static::assertSame('CHK_INV_ANOTHER', $declared[1]->name);
        static::assertSame(['foo_id', 'bar_id'], $declared[1]->columns);
    }

    /**
     * @throws SchemaException
     */
    public function test_introspected_expressions_are_registered(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('invoice');

        static::assertSame([], $this->registry->getIntrospectedExpressions($table));
        $expressions = [
            'invoice' => [
                'CHK_ONE' => 'num_nonnulls(col_a, col_b) <= 1',
                'CHK_TWO' => 'num_nonnulls(col_c, col_d) <= 2',
            ],
        ];

        $this->registry->registerIntrospectedExpressions($schema, $expressions);

        static::assertSame($expressions['invoice'], $this->registry->getIntrospectedExpressions($table));
    }

    /**
     * @throws SchemaException
     */
    public function test_registering_expressions_without_matching_table_clears_previous_entries(): void
    {
        $schema = new Schema();
        $table = $schema->createTable('invoice');

        $this->registry->registerIntrospectedExpressions($schema, [
            'invoice' => ['CHK_ONE' => 'num_nonnulls(col_a, col_b) <= 1'],
        ]);
        static::assertNotEmpty($this->registry->getIntrospectedExpressions($table));

        $this->registry->registerIntrospectedExpressions($schema, [
            'missing' => ['CHK_OTHER' => 'num_nonnulls(col_c, col_d) <= 1'],
        ]);

        static::assertSame([], $this->registry->getIntrospectedExpressions($table));
    }
}
