<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Schema;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorAwareInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckGeneratorInterface;
use App\Infrastructure\Persistence\Doctrine\Contracts\CheckSpecInterface;
use App\Infrastructure\Persistence\Doctrine\Enum\CheckOptions;
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

    private array $alteredTables;

    public function __construct(
        private readonly Comparator $defaultComparator,
        AbstractPlatform&CheckGeneratorAwareInterface $platform,
    ) {
        parent::__construct($platform);
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
            $toTableName = $toTable->getName();

            // CREATE handled by Platform via options
            if (!$fromSchema->hasTable($toTableName)) {
                continue;
            }

            $fromTable = $fromSchema->getTable($toTableName);
            $present = $fromTable->hasOption(CheckOptions::EXISTING->value) ? $fromTable->getOption(CheckOptions::EXISTING->value) : [];

            if (!$toTable->hasOption(CheckOptions::DECLARED->value) || !is_array($toTable->getOption(CheckOptions::DECLARED->value))) {
                if (!empty($present)) {
                    $have = [];
                    foreach ($present as $c) {
                        $have[$c['name']] = (string) $c['expr'];
                    }
                    $dropped = array_map(fn (string $name) => new DroppedCheckSpec($name), array_keys($have));

                    $this->addAlteredTable($fromTable, $toTableName, dropped: $dropped);
                }
                continue;
            }

            /** @var list<CheckSpecInterface> $declared */
            $declared = array_values(array_filter($toTable->getOption(CheckOptions::DECLARED->value), fn ($spec) => $spec instanceof CheckSpecInterface));
            if (empty($declared)) {
                continue;
            }

            // Map present: name => expr (as stored by introspector)
            $have = [];
            foreach ($present as $c) {
                $have[$c['name']] = (string) $c['expr'];
            }

            $added = [];
            $changed = [];
            $declaredNames = [];

            // Normalize each declared spec’s expression with generator (no raw SQL path)
            foreach ($declared as $spec) {
                $declaredNames[] = $spec->name;

                // Add declared spec if not present yet
                if (!array_key_exists($spec->name, $have)) {
                    $added[] = $spec;
                    continue;
                }

                $wantExpr = $this->generator->normalizeExpressionSql($this->generator->buildExpressionSql($spec));
                $haveExpr = $this->generator->normalizeExpressionSql($have[$spec->name]);

                // Change spec if expression is different
                if ($haveExpr !== $wantExpr) {
                    $changed[] = $spec;
                }
            }

            // Drop specs present but not declared
            $dropped = [];
            foreach (array_diff(array_keys($have), $declaredNames) as $dropName) {
                $dropped[] = new DroppedCheckSpec($dropName);
            }

            $this->addAlteredTable($fromTable, $toTableName, $added, $changed, $dropped);
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

    private function addAlteredTable(
        Table $fromTable,
        string $toTableName,
        array $added = [],
        array $changed = [],
        array $dropped = [],
    ): void {
        if (empty($added) && empty($changed) && empty($dropped)) {
            return;
        }

        $tableDiff = new CheckAwareTableDiff($fromTable);

        if (!empty($added)) {
            $tableDiff->addAddedChecks($added);
        }
        if (!empty($changed)) {
            $tableDiff->addChangedChecks($changed);
        }
        if (!empty($dropped)) {
            $tableDiff->addDroppedChecks($dropped);
        }

        $this->alteredTables[$toTableName] = $tableDiff;
    }
}
