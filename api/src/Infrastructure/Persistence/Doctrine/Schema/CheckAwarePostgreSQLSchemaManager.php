<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckAwareSchemaManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;

final class CheckAwarePostgreSQLSchemaManager extends PostgreSQLSchemaManager implements CheckAwareSchemaManagerInterface
{
    use CheckAwareSchemaManagerTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly PostgreSQLPlatform&CheckAwarePlatformInterface $platform
    ) {
        parent::__construct($connection, $platform);
    }
}
