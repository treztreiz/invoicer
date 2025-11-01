<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwareSchemaManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

final readonly class CheckAwareSchemaManagerFactory
{
    /**
     * @return CheckAwareSchemaManagerInterface&AbstractSchemaManager<AbstractPlatform>
     */
    public function createSchemaManager(
        Connection $connection,
        CheckAwarePlatformInterface $platform,
        string $schemaManagerClass,
    ): CheckAwareSchemaManagerInterface&AbstractSchemaManager {
        if (!class_exists($schemaManagerClass)) {
            throw new \InvalidArgumentException(sprintf('SchemaManager class `%s` does not exist.', $schemaManagerClass));
        }

        if (!is_subclass_of($schemaManagerClass, CheckAwareSchemaManagerInterface::class)) {
            throw new \InvalidArgumentException(sprintf('SchemaManager class `%s` must implement `%s`.', $schemaManagerClass, CheckAwareSchemaManagerInterface::class));
        }

        if (!is_subclass_of($schemaManagerClass, AbstractSchemaManager::class)) {
            throw new \InvalidArgumentException(sprintf('SchemaManager class `%s` must inherit from `%s`.', $schemaManagerClass, AbstractSchemaManager::class));
        }

        return new $schemaManagerClass(
            $connection,
            $platform,
        );
    }
}
