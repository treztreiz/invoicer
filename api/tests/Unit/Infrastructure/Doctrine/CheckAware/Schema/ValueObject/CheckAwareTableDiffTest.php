<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema\ValueObject;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class CheckAwareTableDiffTest extends TestCase
{
    public function test_is_empty_initially(): void
    {
        $diff = new CheckAwareTableDiff(new Table('invoice'));

        static::assertTrue($diff->isEmpty());
        static::assertSame([], $diff->getAddedChecks());
        static::assertSame([], $diff->getModifiedChecks());
        static::assertSame([], $diff->getDroppedChecks());
    }

    public function test_registering_added_checks_marks_diff(): void
    {
        $diff = new CheckAwareTableDiff(new Table('invoice'));

        $diff->addAddedChecks([$this->newSpec('CHK_ADD')]);

        static::assertFalse($diff->isEmpty());
        static::assertCount(1, $diff->getAddedChecks());
    }

    public function test_registering_modified_checks_marks_diff(): void
    {
        $diff = new CheckAwareTableDiff(new Table('invoice'));

        $diff->addModifiedChecks([$this->newSpec('CHK_MOD')]);

        static::assertFalse($diff->isEmpty());
        static::assertCount(1, $diff->getModifiedChecks());
    }

    public function test_registering_dropped_checks_marks_diff(): void
    {
        $diff = new CheckAwareTableDiff(new Table('invoice'));

        $diff->addDroppedChecks([$this->newSpec('CHK_DROP')]);

        static::assertFalse($diff->isEmpty());
        static::assertCount(1, $diff->getDroppedChecks());
    }

    #[DataProvider('combinationProvider')]
    public function test_all_combinations_toggle_empty(array $add, array $mod, array $drop, bool $expectedEmpty): void
    {
        $diff = new CheckAwareTableDiff(new Table('invoice'));

        if (!empty($add)) {
            $diff->addAddedChecks($add);
        }
        if (!empty($mod)) {
            $diff->addModifiedChecks($mod);
        }
        if (!empty($drop)) {
            $diff->addDroppedChecks($drop);
        }

        static::assertSame($expectedEmpty, $diff->isEmpty());
    }

    /**
     * @return iterable<string, array{array<int, SoftXorCheckSpec>, array<int, SoftXorCheckSpec>, array<int, SoftXorCheckSpec>, bool}>
     */
    public static function combinationProvider(): iterable
    {
        yield 'all empty' => [[], [], [], true];
        yield 'only added' => [[self::newSpec('A1')], [], [], false];
        yield 'only modified' => [[], [self::newSpec('M1')], [], false];
        yield 'only dropped' => [[], [], [self::newSpec('D1')], false];
        yield 'mixed' => [[self::newSpec('A1')], [self::newSpec('M1')], [self::newSpec('D1')], false];
    }

    private static function newSpec(string $name): CheckSpecInterface
    {
        return new SoftXorCheckSpec($name, ['cols' => ['recurrence_id', 'installment_plan_id']]);
    }
}
