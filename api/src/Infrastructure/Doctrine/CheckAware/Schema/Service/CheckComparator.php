<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckAwarePlatformInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject\CheckAwareTableDiff;
use App\Infrastructure\Doctrine\CheckAware\Spec\DroppedCheckSpec;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

final class CheckComparator extends Comparator
{
    private CheckRegistry $registry;

    private CheckGeneratorInterface $generator;

    /** @var array<string, CheckAwareTableDiff> */
    private array $alteredTables;

    public function __construct(
        private readonly Comparator $defaultComparator,
        AbstractPlatform&CheckAwarePlatformInterface $platform,
    ) {
        parent::__construct($platform);
        $this->registry = $platform->registry;
        $this->generator = $platform->generator;
    }

    /**
     * @throws SchemaException
     */
    public function compareSchemas(Schema $fromSchema, Schema $toSchema): SchemaDiff
    {
        $this->alteredTables = [];
        $schemaDiff = $this->defaultComparator->compareSchemas($fromSchema, $toSchema);

        foreach ($toSchema->getTables() as $toTable) {
            $tableName = $toTable->getName();

            if (!$fromSchema->hasTable($tableName)) {
                continue; // new table must not be compared
            }

            $fromTable = $fromSchema->getTable($tableName);
            $this->compareTableChecks($fromTable, $toTable);
        }

        $schemaDiff->changedTables = array_values([...$schemaDiff->getAlteredTables(), ...$this->alteredTables]);

        return $schemaDiff;
    }

    private function compareTableChecks(Table $fromTable, Table $toTable): void
    {
        $introspectedExpressions = $this->registry->getIntrospectedExpressions($fromTable);
        $introspectedNames = array_keys($introspectedExpressions);

        $declaredSpecs = $this->registry->getDeclaredSpecs($toTable);
        $declaredSpecNames = [];

        if (empty($declaredSpecs)) {
            $dropped = array_map(
                static fn (string $name): DroppedCheckSpec => new DroppedCheckSpec($name),
                array_keys($introspectedExpressions)
            );

            if (!empty($dropped)) {
                $this->addAlteredTable($fromTable, $toTable->getName(), dropped: $dropped);
            }

            return;
        }

        $added = [];
        $modified = [];

        foreach ($declaredSpecs as $spec) {
            $declaredSpecNames[] = $spec->name;

            if (!array_key_exists($spec->name, $introspectedExpressions)) {
                $added[] = $spec;
                continue;
            }

            $declaredExpr = $this->registry->normalizeExpression($this->generator->buildExpressionSQL($spec));
            $introspectedExpr = $this->registry->normalizeExpression($introspectedExpressions[$spec->name]);

            if ($declaredExpr !== $introspectedExpr) {
                $modified[] = $spec;
            }
        }

        $droppedNames = array_values(array_diff($introspectedNames, $declaredSpecNames));
        $dropped = array_map(fn (string $name) => new DroppedCheckSpec($name), $droppedNames);

        $this->addAlteredTable($fromTable, $toTable->getName(), $added, $modified, $dropped);
    }

    /**
     * @param list<CheckSpecInterface> $added
     * @param list<CheckSpecInterface> $modified
     * @param list<CheckSpecInterface> $dropped
     */
    private function addAlteredTable(
        Table $fromTable,
        string $tableName,
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

        $this->alteredTables[$tableName] = $tableDiff;
    }
}
