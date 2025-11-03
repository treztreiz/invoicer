<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Enum\ConstraintTiming;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

final class EnumCheckSpec extends AbstractCheckSpec
{
    /**
     * @param list<string|int> $values
     */
    public function __construct(
        string $name,
        private(set) readonly string $column,
        private(set) readonly array $values,
        private(set) readonly bool $isString,
        ConstraintTiming $timing = ConstraintTiming::IMMEDIATE,
    ) {
        parent::__construct($name, $timing);

        if ('' === trim($this->column)) {
            throw new \InvalidArgumentException('EnumCheckSpec column cannot be empty.');
        }

        if (empty($values)) {
            throw new \InvalidArgumentException('EnumCheckSpec requires at least one value.');
        }
    }

    protected function normalize(CheckNormalizer $normalizer): self
    {
        $values = $normalizer->normalizeValueList($this->values);
        $isString = is_string($values[0]);

        if ($this->isString !== $isString) {
            throw new \InvalidArgumentException('EnumCheckSpec values do not match declared backing type.');
        }

        return new self(
            $normalizer->normalizeConstraintName($this->name),
            $normalizer->normalizeIdentifier($this->column),
            $values,
            $isString,
            $this->timing,
        );
    }
}
