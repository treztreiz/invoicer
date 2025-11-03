<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
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

    public function test_desired_checks_are_appended(): void
    {
        $table = new Table('invoice');

        static::assertSame([], $this->registry->getDeclaredSpecs($table));

        $first = new SoftXorCheckSpec('chk_inv_soft_xor', ['Recurrence_ID', 'installment_plan_id']);
        $second = new SoftXorCheckSpec('CHK_inv_another', ['FOO_ID', 'bar_id']);

        $this->registry->appendDeclaredSpec($table, $first);
        $this->registry->appendDeclaredSpec($table, $second);

        $desired = $this->registry->getDeclaredSpecs($table);

        static::assertCount(2, $desired);
        static::assertTrue($desired[0]->isNormalized());
        static::assertSame('CHK_INV_SOFT_XOR', $desired[0]->name);
        static::assertSame(['recurrence_id', 'installment_plan_id'], $desired[0]->columns);
        static::assertTrue($desired[1]->isNormalized());
        static::assertSame('CHK_INV_ANOTHER', $desired[1]->name);
        static::assertSame(['foo_id', 'bar_id'], $desired[1]->columns);
    }

    public function test_existing_checks_are_mapped(): void
    {
        $table = new Table('invoice');

        static::assertSame([], $this->registry->getIntrospectedExpressions($table));
        $checks = [
            'CHK_ONE' => 'num_nonnulls(col_a, col_b) <= 1',
            'CHK_TWO' => 'num_nonnulls(col_c, col_d) <= 2',
        ];

        $this->registry->setIntrospectedExpressions($table, $checks);

        static::assertSame($checks, $this->registry->getIntrospectedExpressions($table));
    }
}
