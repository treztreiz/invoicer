<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckComparator;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckOptionManager;
use App\Infrastructure\Doctrine\CheckAware\Schema\Trait\CheckAwareSchemaManagerTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @testType sociable-unit
 */
final class CheckAwareSchemaManagerTraitTest extends TestCase
{
    private PostgreSQLCheckAwarePlatform $platform;

    private MockObject&CheckGeneratorInterface $generator;

    private MockObject&Connection $connection;

    protected function setUp(): void
    {
        $this->platform = new PostgreSQLCheckAwarePlatform();
        $this->platform->setCheckOptionManager(new CheckOptionManager());

        $this->generator = self::createMock(CheckGeneratorInterface::class);
        $this->platform->setCheckGenerator($this->generator);

        $this->connection = self::createMock(Connection::class);
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function test_introspect_schema_annotates_with_existing_checks(): void
    {
        $this->generator
            ->expects(static::once())
            ->method('buildIntrospectionSQL')
            ->willReturn('SELECT * FROM check_constraints');

        $this->generator
            ->expects(static::once())
            ->method('mapIntrospectionRow')
            ->willReturnCallback(fn($row) => [
                'table' => $row['table_name'],
                'name' => $row['name'],
                'expr' => $row['def'],
            ]);

        $this->connection
            ->expects(static::once())
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM check_constraints')
            ->willReturn([
                ['table_name' => 'dummy_table', 'name' => 'CHK_STUB', 'def' => 'dummy_expr'],
            ]);

        $schemaManager = new SchemaManagerStub($this->connection, $this->platform);

        $annotated = $schemaManager->introspectSchema();
        $checks = $this->platform->optionManager->existing($annotated->getTable('dummy_table'));

        static::assertNotEmpty($checks);
        static::assertSame('CHK_STUB', $checks[0]['name']);
    }

    public function test_create_comparator_returns_check_aware_comparator(): void
    {
        $schemaManager = new SchemaManagerStub($this->connection, $this->platform);
        $comparator = $schemaManager->createComparator();

        static::assertInstanceOf(CheckComparator::class, $comparator);

        $platformProperty = new \ReflectionProperty(Comparator::class, 'platform');
        $platformProperty->setAccessible(true);

        static::assertSame($this->platform, $platformProperty->getValue($comparator));
    }
}

abstract class AbstractSchemaManagerStub
{
    public function __construct(
        protected Connection $connection,
        protected PostgreSQLPlatform&CheckAwarePlatformInterface $platform,
    ) {
    }

    /**
     * @throws SchemaException
     */
    public function introspectSchema(): Schema
    {
        return new Schema([new Table('dummy_table')]);
    }

    public function createComparator(): Comparator
    {
        return new Comparator();
    }
}

class SchemaManagerStub extends AbstractSchemaManagerStub
{
    use CheckAwareSchemaManagerTrait;
}
