<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Platform\Trait;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckAwareSchemaManagerFactory;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckOptionManager;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use Doctrine\DBAL\Schema\Table;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Helper methods shared across check-aware DBAL platforms.
 *
 * @property \App\Infrastructure\Doctrine\Schema\\App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckOptionManager $optionManager
 */
trait CheckAwarePlatformTrait
{
    private(set) readonly CheckAwareSchemaManagerFactory $schemaManagerFactory;

    private(set) readonly CheckOptionManager $optionManager;

    private(set) readonly CheckGeneratorInterface $generator;

    #[Required]
    public function setSchemaManagerFactory(CheckAwareSchemaManagerFactory $schemaManagerFactory): void
    {
        $this->schemaManagerFactory = $schemaManagerFactory;
    }

    #[Required]
    public function setCheckGenerator(CheckGeneratorInterface $generator): void
    {
        $this->generator = $generator;
    }

    #[Required]
    public function setCheckOptionManager(CheckOptionManager $optionManager): void
    {
        $this->optionManager = $optionManager;
    }

    /**
     * @return list<CheckSpecInterface> desired checks defined on the schema table
     */
    public function getDesiredChecks(Table $table): array
    {
        return $this->optionManager->desired($table);
    }

    /**
     * Append SQL snippets that create desired checks for a freshly created table.
     *
     * @param list<string> $sql
     */
    public function appendChecksSQL(array &$sql, Table $table): void
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
    public function appendDiffChecksSQL(array &$sql, CheckAwareTableDiff $diff): void
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
}
