<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwareSchemaManagerInterface;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Schema\PostgreSQLCheckAwareSchemaManager;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckAwareSchemaManagerFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class CheckAwareSchemaManagerFactoryTest extends TestCase
{
    private CheckAwareSchemaManagerFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CheckAwareSchemaManagerFactory();
    }

    public function test_check_aware_schema_manager_instance_is_created(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = new PostgreSQLCheckAwarePlatform();

        $manager = $this->factory->createSchemaManager(
            $connection,
            $platform,
            PostgreSQLCheckAwareSchemaManager::class,
        );

        static::assertInstanceOf(PostgreSQLCheckAwareSchemaManager::class, $manager);
    }

    public function test_non_existing_classes_are_rejected(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = new PostgreSQLCheckAwarePlatform();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SchemaManager class `Framework\\Schema\\InexistentManager` does not exist.');

        $this->factory->createSchemaManager(
            $connection,
            $platform,
            'Framework\Schema\InexistentManager',
        );
    }

    public function test_classes_not_implementing_contract_are_rejected(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = new PostgreSQLCheckAwarePlatform();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'SchemaManager class `%s` must implement `%s`.',
                PostgreSQLSchemaManager::class,
                CheckAwareSchemaManagerInterface::class,
            )
        );

        $this->factory->createSchemaManager(
            $connection,
            $platform,
            PostgreSQLSchemaManager::class,
        );
    }

    public function test_classes_not_extending_abstract_manager_are_rejected(): void
    {
        $connection = $this->createMock(Connection::class);
        $platform = new PostgreSQLCheckAwarePlatform();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'SchemaManager class `%s` must inherit from `%s`.',
                InvalidCheckAwareSchemaManager::class,
                AbstractSchemaManager::class,
            )
        );

        $this->factory->createSchemaManager(
            $connection,
            $platform,
            InvalidCheckAwareSchemaManager::class,
        );
    }
}

final class InvalidCheckAwareSchemaManager implements CheckAwareSchemaManagerInterface
{
    public function createComparator(): Comparator
    {
        return new Comparator();
    }

    public function introspectSchema(): Schema
    {
        return new Schema();
    }
}
