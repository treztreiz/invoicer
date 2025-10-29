<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Platform;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckSpecInterface;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckAwareTableDiff;
use App\Infrastructure\Persistence\Doctrine\Schema\CheckOptionManager;
use Doctrine\DBAL\Schema\Table;

/**
 * Helper methods shared across check-aware DBAL platforms.
 *
 * @property CheckOptionManager $optionManager
 */
trait CheckPlatformTrait
{
    /**
     * @return list<CheckSpecInterface> desired checks defined on the schema table
     */
    private function getDesiredChecks(Table $table): array
    {
        return $this->optionManager()->desired($table);
    }

    /**
     * Append SQL snippets that create desired checks for a freshly created table.
     *
     * @param list<string> $sql
     */
    private function appendChecksSQL(array &$sql, Table $table): void
    {
        foreach ($this->getDesiredChecks($table) as $spec) {
            $sql[] = $this->generator->buildAddCheckSQL($table->getQuotedName($this), $spec);
        }
    }

    /**
     * Append SQL snippets updating checks for an altered table diff.
     *
     * @param list<string> $sql
     */
    private function appendDiffChecksSQL(array &$sql, CheckAwareTableDiff $diff): void
    {
        $tableNameSql = $diff->getOldTable()->getQuotedName($this);

        foreach ($diff->getAddedChecks() as $spec) {
            $sql[] = $this->generator->buildAddCheckSQL($tableNameSql, $spec);
        }

        foreach ($diff->getModifiedChecks() as $spec) {
            $sql[] = $this->generator->buildDropCheckSQL($tableNameSql, $spec);
            $sql[] = $this->generator->buildAddCheckSQL($tableNameSql, $spec);
        }

        foreach ($diff->getDroppedChecks() as $spec) {
            $sql[] = $this->generator->buildDropCheckSQL($tableNameSql, $spec);
        }
    }

    private function optionManager(): CheckOptionManager
    {
        return $this->optionManager;
    }
}
