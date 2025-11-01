<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Enum\CheckOption;
use Doctrine\DBAL\Schema\Table;

/**
 * Centralises access to desired (schema-declared) and existing (DB introspected) check metadata.
 *
 * Intentionally non-final so callers can substitute a test double when verifying interactions.
 */
class CheckOptionManager
{
    /**
     * @return list<CheckSpecInterface> desired checks defined on the schema table
     */
    public function desired(Table $table): array
    {
        $option = $this->getOption($table, CheckOption::DESIRED);

        return array_values(
            array_filter(
                $option,
                static fn ($spec): bool => $spec instanceof CheckSpecInterface,
            )
        );
    }

    /**
     * Register a desired check spec on the given schema table.
     */
    public function appendDesired(Table $table, CheckSpecInterface $spec): void
    {
        $current = $this->getOption($table, CheckOption::DESIRED);

        $table->addOption(CheckOption::DESIRED->value, [
            ...$current,
            $spec,
        ]);
    }

    /**
     * @param list<array{name: string, expr: string}> $checks
     */
    public function setExisting(Table $table, array $checks): void
    {
        $table->addOption(CheckOption::EXISTING->value, $checks);
    }

    /**
     * @return list<array{name: string, expr: string}> existing checks introspected from the current database
     */
    public function existing(Table $table): array
    {
        $option = $this->getOption($table, CheckOption::EXISTING);

        return array_map(
            static fn (array $entry): array => [
                'name' => (string) $entry['name'],
                'expr' => (string) $entry['expr'],
            ],
            $option,
        );
    }

    /**
     * @return array<string, string> name => expr
     */
    public function existingByName(Table $table): array
    {
        $existing = $this->existing($table);

        return array_column($existing, 'expr', 'name');
    }

    /**
     * @template T
     *
     * @param callable(array{name: string, expr: string}):T $mapper
     *
     * @return list<T>
     */
    public function mapExisting(Table $table, callable $mapper): array
    {
        return array_map($mapper, $this->existing($table));
    }

    /**
     * @param array<string, string> $existingByName
     * @param list<string>          $desiredNames
     *
     * @return list<string> Dropped check names
     */
    public function diffDropped(array $existingByName, array $desiredNames): array
    {
        return array_values(array_diff(array_keys($existingByName), $desiredNames));
    }

    /**
     * @return list<mixed>
     */
    private function getOption(Table $table, CheckOption $option): array
    {
        return $table->hasOption($option->value)
            ? $table->getOption($option->value)
            : [];
    }
}
