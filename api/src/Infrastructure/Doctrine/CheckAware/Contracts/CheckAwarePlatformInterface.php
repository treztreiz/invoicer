<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Contracts;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckRegistry;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface CheckAwarePlatformInterface
{
    public CheckRegistry $registry {
        get;
    }

    public CheckGeneratorInterface $generator {
        get;
    }

    public function setCheckRegistry(CheckRegistry $registry): void;

    public function setCheckGenerator(CheckGeneratorInterface $generator): void;

    /**
     * @return list<CheckSpecInterface> desired checks defined on the schema table
     */
    public function getDesiredChecks(Table $table): array;

    /**
     * Append SQL snippets that create desired checks for a freshly created table.
     *
     * @param list<string> $sql
     */
    public function appendChecksSQL(array &$sql, Table $table): void;

    /**
     * Append SQL snippets updating checks for an altered table diff.
     *
     * @param list<string> $sql
     */
    public function appendDiffChecksSQL(array &$sql, CheckAwareTableDiff $diff): void;
}
