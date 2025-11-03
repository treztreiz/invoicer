<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\NormalizableCheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Enum\CheckOption;
use Doctrine\DBAL\Schema\Table;

/**
 * Stores and retrieves check metadata (declared specs and introspected expressions)
 * while ensuring canonical formatting via the provided CheckNormalizer.
 *
 * @phpstan-type IntrospectedExpressionMap array<string, string>
 */
final readonly class CheckRegistry
{
    public function __construct(private CheckNormalizer $normalizer)
    {
    }

    /**
     * @return list<CheckSpecInterface>
     */
    public function getDeclaredSpecs(Table $table): array
    {
        return $this->getOption($table, CheckOption::DECLARED);
    }

    public function appendDeclaredSpec(Table $table, CheckSpecInterface $spec): void
    {
        $declared = $this->getDeclaredSpecs($table);
        $declared[] = $this->normalizeSpec($spec);

        $table->addOption(CheckOption::DECLARED->value, $declared);
    }

    /**
     * @param IntrospectedExpressionMap $expressions
     */
    public function setIntrospectedExpressions(Table $table, array $expressions): void
    {
        $normalized = [];

        foreach ($expressions as $name => $expr) {
            $normalized[$this->normalizer->normalizeConstraintName($name)] = $expr;
        }

        $table->addOption(CheckOption::INTROSPECTED->value, $normalized);
    }

    /**
     * @return IntrospectedExpressionMap
     */
    public function getIntrospectedExpressions(Table $table): array
    {
        return $this->getOption($table, CheckOption::INTROSPECTED);
    }

    public function normalizeExpression(string $expression): string
    {
        return $this->normalizer->canonicalExpression($expression);
    }

    private function normalizeSpec(CheckSpecInterface $spec): CheckSpecInterface
    {
        if ($spec instanceof NormalizableCheckSpecInterface) {
            $spec = $spec->normalizeWith($this->normalizer);

            if (!$spec->isNormalized()) {
                throw new \LogicException(sprintf('%s::normalizeWith() must return a normalized spec.', $spec::class));
            }
        }

        return $spec;
    }

    /**
     * @template T
     *
     * @return array<T>
     */
    private function getOption(Table $table, CheckOption $option): array
    {
        if (!$table->hasOption($option->value)) {
            return [];
        }

        /** @var list<T> $value */
        $value = $table->getOption($option->value);

        return $value;
    }
}
