<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckIntrospector;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckOptionManager;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class CheckIntrospectorTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_introspect_maps_rows_to_table_checks(): void
    {
        $generator = static::createMock(CheckGeneratorInterface::class);
        $generator
            ->expects(static::once())
            ->method('buildIntrospectionSQL')
            ->willReturn('SELECT checks');

        $generator
            ->expects(static::exactly(2))
            ->method('mapIntrospectionRow')
            ->willReturnMap([
                [['table_name' => 'invoice', 'name' => 'CHK_INV', 'def' => 'expr'], ['table' => 'invoice', 'name' => 'CHK_INV', 'expr' => 'expr']],
                [['table_name' => 'quote', 'name' => 'CHK_QUO', 'def' => 'expr'], ['table' => 'quote', 'name' => 'CHK_QUO', 'expr' => 'expr']],
            ]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(static::once())
            ->method('fetchAllAssociative')
            ->with('SELECT checks')
            ->willReturn([
                ['table_name' => 'invoice', 'name' => 'CHK_INV', 'def' => 'expr'],
                ['table_name' => 'quote', 'name' => 'CHK_QUO', 'def' => 'expr'],
            ]);

        $optionManager = new CheckOptionManager(new CheckNormalizer());

        $introspector = new CheckIntrospector($generator, $optionManager);
        $checks = $introspector->introspect($connection);

        static::assertSame(
            [
                'invoice' => [['name' => 'CHK_INV', 'expr' => 'expr']],
                'quote' => [['name' => 'CHK_QUO', 'expr' => 'expr']],
            ],
            $checks
        );
    }

    /**
     * @throws SchemaException
     */
    public function test_annotate_sets_existing_checks_and_ignores_unknown_tables(): void
    {
        $generator = static::createStub(CheckGeneratorInterface::class);
        $optionManager = static::createMock(CheckOptionManager::class);

        $schema = new Schema();
        $invoice = $schema->createTable('invoice');
        $schema->createTable('quote');

        $checks = [
            'invoice' => [['name' => 'CHK_INV', 'expr' => 'expr']],
            'missing' => [['name' => 'CHK_OTHER', 'expr' => 'expr']],
        ];

        $optionManager
            ->expects(static::once())
            ->method('setExisting')
            ->with(
                static::identicalTo($invoice),
                $checks['invoice']
            );

        $introspector = new CheckIntrospector($generator, $optionManager);
        $annotated = $introspector->annotate($schema, $checks);

        static::assertSame($schema, $annotated, 'Annotate should return the original schema instance.');
    }
}
