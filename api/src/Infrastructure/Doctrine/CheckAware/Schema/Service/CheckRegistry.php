<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Spec\AbstractCheckSpec;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Stores and retrieves check metadata (declared specs and introspected expressions)
 * while ensuring canonical formatting via the provided CheckNormalizer.
 *
 * @phpstan-type DeclaredSpecMap array<string, CheckSpecInterface>
 * @phpstan-type IntrospectedExpressionMap array<string, string>
 */
class CheckRegistry
{
    /** @var \SplObjectStorage<Table, DeclaredSpecMap> */
    private \SplObjectStorage $declaredSpecs;

    /** @var \SplObjectStorage<Table, IntrospectedExpressionMap> */
    private \SplObjectStorage $introspectedExpressions;

    public function __construct(private readonly CheckNormalizer $normalizer)
    {
        $this->declaredSpecs = new \SplObjectStorage();
        $this->introspectedExpressions = new \SplObjectStorage();
    }

    /**
     * @return list<CheckSpecInterface>
     */
    public function getDeclaredSpecs(Table $table): array
    {
        return array_values($this->declaredSpecs[$table] ?? []);
    }

    public function appendDeclaredSpec(Table $table, CheckSpecInterface $spec): void
    {
        $normalized = $this->normalizeSpec($spec);

        $bucket = $this->declaredSpecs[$table] ?? [];
        $bucket[$normalized->name] = $normalized;

        $this->declaredSpecs[$table] = $bucket;
    }

    /**
     * @param array<string, IntrospectedExpressionMap> $schemaExpressions
     */
    public function registerIntrospectedExpressions(Schema $schema, array $schemaExpressions): void
    {
        $this->introspectedExpressions = new \SplObjectStorage();

        foreach ($schema->getTables() as $table) {
            $tableExpressions = $schemaExpressions[$table->getName()] ?? [];
            $normalizedExpressions = [];

            if (empty($tableExpressions)) {
                continue;
            }

            foreach ($tableExpressions as $name => $expr) {
                $normalizedExpressions[$this->normalizer->normalizeConstraintName($name)] = $expr;
            }

            $this->introspectedExpressions[$table] = $normalizedExpressions;
        }
    }

    /**
     * @return IntrospectedExpressionMap
     */
    public function getIntrospectedExpressions(Table $table): array
    {
        return $this->introspectedExpressions[$table] ?? [];
    }

    public function normalizeExpression(string $expression): string
    {
        return $this->normalizer->normalizeExpression($expression);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function normalizeSpec(CheckSpecInterface $spec): CheckSpecInterface
    {
        if (!($spec instanceof AbstractCheckSpec)) {
            throw new \LogicException(sprintf('%s must be an instance of %s.', $spec::class, AbstractCheckSpec::class));
        }

        $spec = $spec->normalizeWith($this->normalizer);

        if (!$spec->normalized) {
            throw new \LogicException(sprintf('%s::normalizeWith() must return a normalized spec.', $spec::class));
        }

        return $spec;
    }
}
