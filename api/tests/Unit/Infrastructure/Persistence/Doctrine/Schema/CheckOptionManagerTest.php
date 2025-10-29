<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Schema\CheckOptionManager;
use App\Infrastructure\Persistence\Doctrine\ValueObject\SoftXorCheckSpec;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CheckOptionManagerTest extends TestCase
{
    private readonly CheckOptionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new CheckOptionManager();
    }

    public function test_desired_checks_are_appended(): void
    {
        $table = new Table('invoice');

        static::assertSame([], $this->manager->desired($table));

        $first = new SoftXorCheckSpec('CHK_INV_SOFT_XOR', ['cols' => ['recurrence_id', 'installment_plan_id']]);
        $second = new SoftXorCheckSpec('CHK_INV_ANOTHER', ['cols' => ['foo_id', 'bar_id']]);

        $this->manager->appendDesired($table, $first);
        $this->manager->appendDesired($table, $second);

        static::assertSame([$first, $second], $this->manager->desired($table));
    }

    public function test_existing_checks_are_mapped(): void
    {
        $table = new Table('invoice');

        static::assertSame([], $this->manager->existing($table));
        static::assertSame([], $this->manager->existingByName($table));
        static::assertSame([], $this->manager->mapExisting($table, static fn () => null));

        $checks = [
            ['name' => 'CHK_ONE', 'expr' => 'num_nonnulls(col_a, col_b) <= 1'],
            ['name' => 'CHK_TWO', 'expr' => 'num_nonnulls(col_c, col_d) <= 2'],
        ];

        $this->manager->setExisting($table, $checks);

        static::assertSame($checks, $this->manager->existing($table));
        static::assertSame(
            [
                'CHK_ONE' => 'num_nonnulls(col_a, col_b) <= 1',
                'CHK_TWO' => 'num_nonnulls(col_c, col_d) <= 2',
            ],
            $this->manager->existingByName($table),
        );
        static::assertSame(
            ['CHK_ONE', 'CHK_TWO'],
            $this->manager->mapExisting($table, static fn (array $check): string => $check['name']),
        );
    }

    /**
     * @param array<string,string> $existing
     * @param list<string>         $desired
     * @param list<string>         $expected
     */
    #[DataProvider('diffDroppedProvider')]
    public function test_checks_are_dropped_from_diff(array $existing, array $desired, array $expected): void
    {
        static::assertSame($expected, $this->manager->diffDropped($existing, $desired));
    }

    /**
     * @return iterable<string, array{array<string,string>, list<string>, list<string>}>
     */
    public static function diffDroppedProvider(): iterable
    {
        $existing = [
            'CHK_ONE' => 'expr_one',
            'CHK_TWO' => 'expr_two',
            'CHK_THREE' => 'expr_three',
        ];

        yield 'drop two checks' => [$existing, ['CHK_ONE'], ['CHK_TWO', 'CHK_THREE']];
        yield 'drop none' => [$existing, ['CHK_ONE', 'CHK_TWO', 'CHK_THREE'], []];
        yield 'drop all' => [$existing, [], ['CHK_ONE', 'CHK_TWO', 'CHK_THREE']];
    }
}
