<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwareSchemaManagerInterface;
use App\Infrastructure\Doctrine\CheckAware\Schema\Trait\CheckAwareSchemaManagerTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

final class PostgreSQLCheckAwareSchemaManager extends PostgreSQLSchemaManager implements CheckAwareSchemaManagerInterface
{
    use CheckAwareSchemaManagerTrait;

    public function __construct(
        private readonly Connection $connection,
        /** @var PostgreSQLPlatform&CheckAwarePlatformInterface $platform */
        private readonly PostgreSQLPlatform&CheckAwarePlatformInterface $platform,
    ) {
        parent::__construct($connection, $platform);
    }
}
