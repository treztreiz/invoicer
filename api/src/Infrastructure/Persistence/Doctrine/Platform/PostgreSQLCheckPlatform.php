<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Platform;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorAwareInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckSpecInterface;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckAwareTableDiff;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

final class PostgreSQLCheckPlatform extends PostgreSQLPlatform implements CheckGeneratorAwareInterface
{
    private(set) PostgreSQLCheckGenerator $generator;

    public function __construct()
    {
        $this->generator = new PostgreSQLCheckGenerator($this);
    }

    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES): array
    {
        $sql = parent::getCreateTableSQL($table, $createFlags);

        if ($table->hasOption('app_checks') && is_array($table->getOption('app_checks'))) {
            /** @var list<CheckSpecInterface> $declared */
            $declared = array_values(array_filter($table->getOption('app_checks'), fn ($spec) => $spec instanceof CheckSpecInterface));
            if (!empty($declared)) {
                foreach ($declared as $spec) {
                    $sql[] = $this->generator->buildAddCheckSql($table->getQuotedName($this), $spec);
                }
            }
        }

        return $sql;
    }

    public function getCreateTablesSQL(array $tables): array
    {
        $sql = parent::getCreateTablesSQL($tables);

        foreach ($tables as $table) {
            if ($table->hasOption('app_checks') && is_array($table->getOption('app_checks'))) {
                /** @var list<CheckSpecInterface> $declared */
                $declared = array_values(array_filter($table->getOption('app_checks'), fn ($spec) => $spec instanceof CheckSpecInterface));
                if (!empty($declared)) {
                    foreach ($declared as $spec) {
                        $sql[] = $this->generator->buildAddCheckSql($table->getQuotedName($this), $spec);
                    }
                }
            }
        }

        return $sql;
    }

    public function getAlterTableSQL(TableDiff $diff): array
    {
        $sql = parent::getAlterTableSQL($diff);

        if ($diff instanceof CheckAwareTableDiff) {
            $tableNameSql = $diff->getOldTable()->getQuotedName($this);

            foreach ($diff->getAddedChecks() as $spec) {
                $sql[] = $this->generator->buildAddCheckSql($tableNameSql, $spec);
            }
            foreach ($diff->getChangedChecks() as $spec) {
                $sql[] = $this->generator->buildDropCheckSql($tableNameSql, $spec);
                $sql[] = $this->generator->buildAddCheckSql($tableNameSql, $spec);
            }
            foreach ($diff->getDroppedChecks() as $spec) {
                $sql[] = $this->generator->buildDropCheckSql($tableNameSql, $spec);
            }
        }

        return $sql;
    }
}
