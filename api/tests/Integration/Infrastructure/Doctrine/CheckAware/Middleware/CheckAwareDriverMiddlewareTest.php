<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Doctrine\CheckAware\Middleware;

use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use App\Infrastructure\Doctrine\CheckAware\Schema\PostgreSQLCheckAwareSchemaManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @testType integration
 */
final class CheckAwareDriverMiddlewareTest extends KernelTestCase
{
    use ResetDatabase;

    /**
     * @throws Exception
     */
    public function test_middleware_swaps_in_check_aware_platform_and_schema_manager(): void
    {
        self::bootKernel();

        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);

        $platform = $connection->getDatabasePlatform();

        static::assertInstanceOf(PostgreSQLCheckAwarePlatform::class, $platform);

        $schemaManager = $platform->createSchemaManager($connection);

        static::assertInstanceOf(PostgreSQLCheckAwareSchemaManager::class, $schemaManager);
    }
}
