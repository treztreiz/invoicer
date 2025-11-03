<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Schema\Service;

final class CheckNormalizer
{
    private const string CONSTRAINT_PATTERN = '/^[A-Z0-9_]+$/';
    private const string IDENTIFIER_PATTERN = '/^[a-z0-9_]+$/';
    private const string VALUE_PATTERN = '/^[A-Za-z0-9_]+$/';

    public function normalizeConstraintName(string $name): string
    {
        $normalized = strtoupper(trim($name));

        if (!preg_match(self::CONSTRAINT_PATTERN, $normalized)) {
            throw new \InvalidArgumentException(sprintf('Constraint name "%s" must match %s.', $name, self::CONSTRAINT_PATTERN));
        }

        return $normalized;
    }

    public function normalizeIdentifier(string $identifier): string
    {
        $normalized = strtolower(trim($identifier));

        if (!preg_match(self::IDENTIFIER_PATTERN, $normalized)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" must match %s.', $identifier, self::IDENTIFIER_PATTERN));
        }

        return $normalized;
    }

    public function normalizeValue(string $value): string
    {
        $normalized = trim($value);

        if (!preg_match(self::VALUE_PATTERN, $normalized)) {
            throw new \InvalidArgumentException(sprintf('Value "%s" must match %s.', $value, self::VALUE_PATTERN));
        }

        return $normalized;
    }

    /**
     * @param array<int, string|int> $values
     *
     * @return array<int, string|int>
     */
    public function normalizeValueList(array $values): array
    {
        if ([] === $values) {
            throw new \InvalidArgumentException('Values list cannot be empty.');
        }

        $firstType = null;

        foreach ($values as $index => $value) {
            if (!is_string($value) && !is_int($value)) {
                throw new \InvalidArgumentException('Values must be strings or integers.');
            }

            $type = is_string($value) ? 'string' : 'int';
            $firstType ??= $type;

            if ($type !== $firstType) {
                throw new \InvalidArgumentException('Values list must contain a uniform scalar type.');
            }

            if ('string' === $type) {
                $values[$index] = $this->normalizeValue($value);
            }
        }

        return array_values($values);
    }

    public function canonicalExpression(string $sql): string
    {
        $expr = strtolower(trim($sql));

        $expr = preg_replace('/^check/', '', $expr) ?? $expr;
        $expr = preg_replace('/::[a-z0-9_]+(?:\[\])?/', '', $expr) ?? $expr;
        $expr = preg_replace('/\s+/', '', $expr) ?? $expr;
        $expr = str_replace(['"', "'", '(', ')', '[', ']'], '', $expr);

        return $expr;
    }
}
