<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Filters documents by customer identity (legal/first/last name) using a single `search` parameter.
 */
final class CustomerSearchFilter extends AbstractFilter
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function filterProperty(
        string $property,
        mixed $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ('search' !== $property) {
            return;
        }

        if (\is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!\is_string($value)) {
            return;
        }

        $term = trim($value);
        if ('' === $term) {
            return;
        }

        $parameterName = $queryNameGenerator->generateParameterName('customer_search');
        $customerAlias = $this->ensureCustomerJoin($queryBuilder, $queryNameGenerator);

        $normalizedValue = sprintf('%%%s%%', mb_strtolower($term));

        $expression = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->like(sprintf('LOWER(%s.legalName)', $customerAlias), sprintf(':%s', $parameterName)),
            $queryBuilder->expr()->like(sprintf('LOWER(%s.name.firstName)', $customerAlias), sprintf(':%s', $parameterName)),
            $queryBuilder->expr()->like(sprintf('LOWER(%s.name.lastName)', $customerAlias), sprintf(':%s', $parameterName)),
        );

        $queryBuilder
            ->andWhere($expression)
            ->setParameter($parameterName, $normalizedValue);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'search' => [
                'property' => 'search',
                'type' => 'string',
                'required' => false,
                'description' => 'Filters quotes by customer legal name or contact person (first/last names).',
            ],
        ];
    }

    private function ensureCustomerJoin(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): string
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $joins = $queryBuilder->getDQLPart('join');
        $target = sprintf('%s.customer', $rootAlias);

        /** @var list<Join> $existingJoins */
        $existingJoins = $joins[$rootAlias] ?? [];

        foreach ($existingJoins as $join) {
            if ($join->getJoin() === $target) {
                return $join->getAlias();
            }
        }

        $alias = $queryNameGenerator->generateJoinAlias('customer');
        $queryBuilder->leftJoin($target, $alias);

        return $alias;
    }
}
