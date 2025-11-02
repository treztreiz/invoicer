<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

/** @phpstan-type EnumCheckPayload array{column: non-empty-string, values: non-empty-list<string|int>, is_string: bool} */
final class EnumCheckSpec extends AbstractCheckSpec
{
    /**
     * @param non-empty-string $name
     * @phpstan-param EnumCheckPayload&array{is_string?: bool} $expr
     */
    public function __construct(
        private(set) readonly string $name,
        private(set) readonly array $expr,
        private(set) readonly bool $deferrable = false,
    ) {
        if (!array_key_exists('column', $this->expr) || !is_string($this->expr['column']) || '' === trim($this->expr['column'])) {
            throw new \InvalidArgumentException('EnumCheckSpec expr["column"] cannot be empty.');
        }

        if (!array_key_exists('values', $this->expr) || !is_array($this->expr['values']) || [] === $this->expr['values']) {
            throw new \InvalidArgumentException('EnumCheckSpec expr["values"] requires at least one value.');
        }

        if (array_key_exists('is_string', $this->expr) && !is_bool($this->expr['is_string'])) {
            throw new \InvalidArgumentException('EnumCheckSpec expr["is_string"] must be a boolean when provided.');
        }
    }

    public function normalizeWith(CheckNormalizer $normalizer): self
    {
        if ($this->normalized) {
            return $this;
        }

        $values = $normalizer->normalizeValueList($this->expr['values']);
        $isString = is_string($values[0]);

        if (array_key_exists('is_string', $this->expr) && $this->expr['is_string'] !== $isString) {
            throw new \InvalidArgumentException('EnumCheckSpec values do not match declared backing type.');
        }

        return self::fromNormalized(
            $normalizer->normalizeConstraintName($this->name),
            [
                'column' => $normalizer->normalizeIdentifier($this->expr['column']),
                'values' => $values,
                'is_string' => $isString,
            ],
            $this->deferrable,
        );
    }
}
