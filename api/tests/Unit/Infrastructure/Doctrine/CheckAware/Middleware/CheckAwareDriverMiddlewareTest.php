<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Doctrine\CheckAware\Middleware;

use App\Infrastructure\Doctrine\CheckAware\Middleware\CheckAwareDriverMiddleware;
use App\Infrastructure\Doctrine\CheckAware\Platform\PostgreSQLCheckAwarePlatform;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class CheckAwareDriverMiddlewareTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_version_aware_driver_is_wrapped_with_check_aware_platform(): void
    {
        $basePlatform = new PostgreSQLPlatform();
        $expectedPlatform = new PostgreSQLCheckAwarePlatform();

        /** @var MockObject&VersionAwarePlatformDriverStub $driverStub */
        $driverStub = static::getMockBuilder(VersionAwarePlatformDriverStub::class)
            ->setConstructorArgs([$basePlatform])
            ->onlyMethods(['getSchemaManager', 'connect', 'getDatabasePlatform', 'getExceptionConverter'])
            ->getMock();

        $middleware = new CheckAwareDriverMiddleware(
            $driverStub,
            [$expectedPlatform],
        );

        $platform = $middleware->createDatabasePlatformForVersion('16.0');

        static::assertSame($expectedPlatform, $platform);
    }
}

abstract readonly class VersionAwarePlatformDriverStub implements VersionAwarePlatformDriver
{
    public function __construct(private AbstractPlatform $platform)
    {
    }

    public function createDatabasePlatformForVersion($version): AbstractPlatform
    {
        return $this->platform;
    }
}
