<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckAwareSchemaManagerFactory;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use App\Infrastructure\Doctrine\CheckAware\Spec\SoftXorCheckSpec;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class CheckAwarePlatformTraitTest extends TestCase
{
    private CheckGeneratorInterface&MockObject $generator;

    private PostgreSQLCheckAwarePlatform $platform;

    protected function setUp(): void
    {
        $this->generator = $this->createMock(CheckGeneratorInterface::class);

        $this->platform = new PostgreSQLCheckAwarePlatform();
        $this->platform->setSchemaManagerFactory(new CheckAwareSchemaManagerFactory());
        $this->platform->setCheckRegistry(new CheckRegistry(new CheckNormalizer()));
        $this->platform->setCheckGenerator($this->generator);
    }

    public function test_append_checks_sql_appends_generator_output(): void
    {
        $table = new Table('invoice');

        $spec = new SoftXorCheckSpec('CHK_ADD', ['columns' => ['col_a', 'col_b']]);

        $this->platform->registry->appendDeclaredSpec($table, $spec);

        $this->generator
            ->expects(static::once())
            ->method('buildAddCheckSQL')
            ->with('invoice', static::callback(static fn($input): bool => $input->isNormalized()))
            ->willReturn('ADD CHECK SQL');

        $sql = ['BASE'];
        $this->platform->appendChecksSQL($sql, $table);

        static::assertSame(['BASE', 'ADD CHECK SQL'], $sql);
    }

    public function test_append_diff_checks_sql_emits_expected_sequence(): void
    {
        $diff = new CheckAwareTableDiff(new Table('invoice'));
        $added = new SoftXorCheckSpec('CHK_ADD', ['columns' => ['col_a', 'col_b']]);
        $modified = new SoftXorCheckSpec('CHK_MOD', ['columns' => ['col_c', 'col_d']]);
        $dropped = new SoftXorCheckSpec('CHK_DROP', ['columns' => ['col_e', 'col_f']]);

        $diff->addAddedChecks([$added]);
        $diff->addModifiedChecks([$modified]);
        $diff->addDroppedChecks([$dropped]);

        // Verify ADD operations fire for the new spec first, then the modified one before re-adding.
        $this->generator
            ->expects(static::exactly(2))
            ->method('buildAddCheckSQL')
            ->willReturnMap([
                ['invoice', $added, 'ADD CHK_ADD'],
                ['invoice', $modified, 'ADD CHK_MOD'],
            ]);

        // Drops should target the modified check prior to re-creation, then the fully dropped constraint.
        $this->generator
            ->expects(static::exactly(2))
            ->method('buildDropCheckSQL')
            ->willReturnMap([
                ['invoice', $modified, 'DROP CHK_MOD'],
                ['invoice', $dropped, 'DROP CHK_DROP'],
            ]);

        $sql = ['BASE'];
        $this->platform->appendDiffChecksSQL($sql, $diff);

        static::assertSame(
            ['BASE', 'ADD CHK_ADD', 'DROP CHK_MOD', 'ADD CHK_MOD', 'DROP CHK_DROP'],
            $sql
        );
    }
}
