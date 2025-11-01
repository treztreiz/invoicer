<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\ValueObject;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

final class CheckAwareTableDiff extends TableDiff
{
    /** @var list<CheckSpecInterface> */
    private array $addedChecks = [];

    /** @var list<CheckSpecInterface> */
    private array $modifiedChecks = [];

    /** @var list<CheckSpecInterface> */
    private array $droppedChecks = [];

    public function __construct(Table $oldTable)
    {
        parent::__construct(
            tableName: $oldTable->getName(),
            fromTable: $oldTable
        );
    }

    /** @param list<CheckSpecInterface> $spec */
    public function addAddedChecks(array $spec): void
    {
        $this->addedChecks = [...$this->addedChecks, ...$spec];
    }

    /** @param list<CheckSpecInterface> $spec */
    public function addModifiedChecks(array $spec): void
    {
        $this->modifiedChecks = [...$this->modifiedChecks, ...$spec];
    }

    /** @param list<CheckSpecInterface> $spec */
    public function addDroppedChecks(array $spec): void
    {
        $this->droppedChecks = [...$this->droppedChecks, ...$spec];
    }

    /** @return list<CheckSpecInterface> */
    public function getAddedChecks(): array
    {
        return $this->addedChecks;
    }

    /** @return list<CheckSpecInterface> */
    public function getModifiedChecks(): array
    {
        return $this->modifiedChecks;
    }

    /** @return list<CheckSpecInterface> */
    public function getDroppedChecks(): array
    {
        return $this->droppedChecks;
    }

    public function isEmpty(): bool
    {
        if (
            count($this->addedChecks) > 0
            || count($this->modifiedChecks) > 0
            || count($this->droppedChecks) > 0
        ) {
            return false;
        }

        return parent::isEmpty();
    }
}
