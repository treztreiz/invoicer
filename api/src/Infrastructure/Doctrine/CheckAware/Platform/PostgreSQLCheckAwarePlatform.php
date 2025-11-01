<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Platform;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Doctrine\CheckAware\Platform\Trait\CheckAwarePlatformTrait;
use App\Infrastructure\Doctrine\CheckAware\Schema\PostgreSQLCheckAwareSchemaManager;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQL120Platform;
use Doctrine\DBAL\Schema\PostgreSQLSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(
    calls: [['setCheckGenerator' => ['@'.PostgreSQLCheckGenerator::class]]],
    lazy: true,
)]
final class PostgreSQLCheckAwarePlatform extends PostgreSQL120Platform implements CheckAwarePlatformInterface
{
    use CheckAwarePlatformTrait;

    public function createSchemaManager(Connection $connection): PostgreSQLSchemaManager
    {
        /** @var PostgreSQLCheckAwareSchemaManager $schemaManager */
        $schemaManager = $this->schemaManagerFactory->createSchemaManager(
            $connection,
            $this,
            PostgreSQLCheckAwareSchemaManager::class
        );

        return $schemaManager;
    }

    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES): array
    {
        $sql = parent::getCreateTableSQL($table, $createFlags);

        $this->appendChecksSQL($sql, $table);

        return $sql;
    }

    public function getCreateTablesSQL(array $tables): array
    {
        $sql = parent::getCreateTablesSQL($tables);

        foreach ($tables as $table) {
            $this->appendChecksSQL($sql, $table);
        }

        return $sql;
    }

    public function getAlterTableSQL(TableDiff $diff): array
    {
        $sql = parent::getAlterTableSQL($diff);

        if ($diff instanceof CheckAwareTableDiff) {
            $this->appendDiffChecksSQL($sql, $diff);
        }

        return $sql;
    }
}
