<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckSpecInterface;
use App\Infrastructure\Persistence\Doctrine\ValueObject\DroppedCheckSpec;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

final class CheckComparator extends Comparator
{
    private CheckGeneratorInterface $generator;

    private CheckOptionManager $optionManager;

    /** @var array<string, CheckAwareTableDiff> */
    private array $alteredTables;

    public function __construct(
        private readonly Comparator $defaultComparator,
        AbstractPlatform&CheckAwarePlatformInterface $platform,
    ) {
        parent::__construct($platform);
        $this->generator = $platform->generator;
        $this->optionManager = $platform->optionManager;
    }

    /**
     * @throws SchemaException
     */
    public function compareSchemas(Schema $fromSchema, Schema $toSchema): SchemaDiff
    {
        $this->alteredTables = [];
        $schemaDiff = $this->defaultComparator->compareSchemas($fromSchema, $toSchema);

        foreach ($toSchema->getTables() as $toTable) {
            $toTableName = $toTable->getName();

            if (!$fromSchema->hasTable($toTableName)) {
                continue; // new table handled elsewhere
            }

            $fromTable = $fromSchema->getTable($toTableName);
            $this->compareTableChecks($fromTable, $toTable);
        }

        $alteredTables = array_values(array_merge($schemaDiff->getAlteredTables(), $this->alteredTables));

        return new SchemaDiff(
            $schemaDiff->getCreatedTables(),
            $alteredTables,
            $schemaDiff->getDroppedTables(),
            $fromSchema,
            $schemaDiff->getCreatedSchemas(),
            $schemaDiff->getDroppedSchemas(),
            $schemaDiff->getCreatedSequences(),
            $schemaDiff->getAlteredSequences(),
            $schemaDiff->getDroppedSequences(),
        );
    }

    private function compareTableChecks(Table $existingTable, Table $desiredTable): void
    {
        $desiredChecks = $this->optionManager->desired($desiredTable);

        if (empty($desiredChecks)) {
            $dropped = $this->optionManager->mapExisting(
                $existingTable,
                static fn (array $check): DroppedCheckSpec => new DroppedCheckSpec($check['name']),
            );

            if (!empty($dropped)) {
                $this->addAlteredTable($existingTable, $desiredTable->getName(), dropped: $dropped);
            }

            return;
        }

        $existingChecksByName = $this->optionManager->existingByName($existingTable);

        $added = [];
        $modified = [];
        $desiredNames = [];

        foreach ($desiredChecks as $spec) {
            $desiredNames[] = $spec->name;

            if (!array_key_exists($spec->name, $existingChecksByName)) {
                $added[] = $spec;
                continue;
            }

            $wantedExpr = $this->generator->normalizeExpressionSQL(
                $this->generator->buildExpressionSQL($spec)
            );
            $currentExpr = $this->generator->normalizeExpressionSQL($existingChecksByName[$spec->name]);

            if ($wantedExpr !== $currentExpr) {
                $modified[] = $spec;
            }
        }

        $droppedNames = $this->optionManager->diffDropped($existingChecksByName, $desiredNames);
        $dropped = array_map(fn (string $name) => new DroppedCheckSpec($name), $droppedNames);

        $this->addAlteredTable($existingTable, $desiredTable->getName(), $added, $modified, $dropped);
    }

    /**
     * @param list<CheckSpecInterface> $added
     * @param list<CheckSpecInterface> $modified
     * @param list<CheckSpecInterface> $dropped
     */
    private function addAlteredTable(
        Table $fromTable,
        string $toTableName,
        array $added = [],
        array $modified = [],
        array $dropped = [],
    ): void {
        if (empty($added) && empty($modified) && empty($dropped)) {
            return;
        }

        $tableDiff = new CheckAwareTableDiff($fromTable);

        if (!empty($added)) {
            $tableDiff->addAddedChecks($added);
        }
        if (!empty($modified)) {
            $tableDiff->addModifiedChecks($modified);
        }
        if (!empty($dropped)) {
            $tableDiff->addDroppedChecks($dropped);
        }

        $this->alteredTables[$toTableName] = $tableDiff;
    }
}
