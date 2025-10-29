<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\EventListener;

use App\Infrastructure\Persistence\Doctrine\Attribute\SoftXor;
use App\Infrastructure\Persistence\Doctrine\ValueObject\SoftXorCheckSpec;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;

#[AsDoctrineListener(ToolEvents::postGenerateSchemaTable)]
class SoftXorCheckListener
{
    /**
     * @throws MappingException
     * @throws SchemaException
     */
    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $args): void
    {
        $class = $args->getClassMetadata();
        $table = $args->getClassTable();

        $attrs = $class->getReflectionClass()->getAttributes(SoftXor::class);
        if (!$attrs) {
            return; // entity not annotated
        }

        /** @var SoftXor $cfg */
        $cfg = $attrs[0]->newInstance();
        $colNames = $this->resolveOwningJoinColumns($class, $cfg->properties);

        // Ensure 1–1 shape: UNIQUE per join column (portable)
        $this->ensureUniquePerColumn($table, $colNames);

        $checks = $table->hasOption('app_checks') ? $table->getOption('app_checks') : [];

        $table->addOption('app_checks', [
            ...(array) $checks,
            ...[new SoftXorCheckSpec($cfg->name, ['cols' => $colNames])],
        ]);
    }

    /**
     * @param non-empty-list<string> $properties
     *
     * @return non-empty-list<string> column names on the SAME table (owning side only)
     *
     * @throws MappingException
     */
    private function resolveOwningJoinColumns(ClassMetadata $class, array $properties): array
    {
        $cols = [];
        foreach ($properties as $prop) {
            if ($class->hasField($prop)) {
                $cols[] = $class->getColumnName($prop);
                continue;
            }
            if (!$class->hasAssociation($prop)) {
                throw new \LogicException(sprintf('Unknown property "%s" on %s.', $prop, $class->getName()));
            }
            $assoc = $class->getAssociationMapping($prop);
            if (!$assoc->isToOneOwningSide()) {
                throw new \LogicException(sprintf('Property "%s" on %s is inverse-side; make it owning so its FK lives on table "%s".', $prop, $class->getName(), $class->getTableName()));
            }
            $join = $assoc->joinColumns[0] ?? null; // 1:1 → single join column
            if (null === $join) {
                throw new \LogicException(sprintf('Owning association "%s" has no join column mapping.', $prop));
            }
            $cols[] = $join->name;
        }

        return $cols;
    }

    /**
     * @param non-empty-list<string> $colNames
     *
     * @throws SchemaException
     */
    private function ensureUniquePerColumn(Table $table, array $colNames): void
    {
        foreach ($colNames as $colName) {
            if ($this->hasUniqueIndexOnColumn($table, $colName)) {
                continue; // already unique, do not duplicate
            }
            $uniqName = sprintf('UNIQ_%s_%s', strtoupper($table->getName()), strtoupper($colName));
            // Even if a name collision happens, DBAL will adjust; but we rarely hit this now.
            $table->addUniqueIndex([$colName], $uniqName);
        }
    }

    /** Returns true if ANY unique (or PK) spans exactly this column. */
    private function hasUniqueIndexOnColumn(Table $table, string $colName): bool
    {
        // Primary key also implies uniqueness; check that first.
        if ($table->hasPrimaryKey() && $table->getPrimaryKey()->spansColumns([$colName])) {
            return true;
        }

        foreach ($table->getIndexes() as $index) {
            if ($index->isUnique() && $index->spansColumns([$colName])) {
                return true;
            }
        }

        return false;
    }
}
