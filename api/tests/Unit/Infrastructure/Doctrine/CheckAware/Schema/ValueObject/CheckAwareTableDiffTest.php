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

    /**
     * @param list<CheckSpecInterface> $added
     * @param list<CheckSpecInterface> $modified
     * @param list<CheckSpecInterface> $dropped
     */
    #[DataProvider('combinationProvider')]
    public function test_all_combinations_toggle_empty(
        array $added,
        array $modified,
        array $dropped,
        bool $expectedEmpty,
    ): void {
        $diff = new CheckAwareTableDiff(new Table('invoice'));

        if (!empty($added)) {
            $diff->addAddedChecks($added);
        }
        if (!empty($modified)) {
            $diff->addModifiedChecks($modified);
        }
        if (!empty($dropped)) {
            $diff->addDroppedChecks($dropped);
        }

        static::assertSame($expectedEmpty, $diff->isEmpty());
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private static function newSpec(string $name): CheckSpecInterface
    {
        return new SoftXorCheckSpec($name, ['recurrence_id', 'installment_plan_id']);
    }

    /**
     * @return iterable<string, array{list<CheckSpecInterface>, list<CheckSpecInterface>, list<CheckSpecInterface>, bool}>
     */
    public static function combinationProvider(): iterable
    {
        yield 'all empty' => [[], [], [], true];
        yield 'only added' => [[self::newSpec('A1')], [], [], false];
        yield 'only modified' => [[], [self::newSpec('M1')], [], false];
        yield 'only dropped' => [[], [], [self::newSpec('D1')], false];
        yield 'mixed' => [[self::newSpec('A1')], [self::newSpec('M1')], [self::newSpec('D1')], false];
    }
}
