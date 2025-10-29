<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Platform;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorAwareInterface;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckAwareTableDiff;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckOptionManager;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

final class PostgreSQLCheckPlatform extends PostgreSQLPlatform implements CheckGeneratorAwareInterface
{
    use CheckPlatformTrait;

    private(set) PostgreSQLCheckGenerator $generator;

    public function __construct(private readonly CheckOptionManager $optionManager)
    {
        $this->generator = new PostgreSQLCheckGenerator($this);
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
