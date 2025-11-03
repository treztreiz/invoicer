<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

final class SoftXorCheckSpec extends AbstractCheckSpec
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        string $name,
        private(set) readonly array $columns,
    ) {
        parent::__construct($name);

        if (count($this->columns) < 2) {
            throw new \InvalidArgumentException('SoftXorCheckSpec requires at least two columns.');
        }
    }

    protected function normalize(CheckNormalizer $normalizer): self
    {
        $columns = array_map([$normalizer, 'normalizeIdentifier'], $this->columns);

        return new self(
            $normalizer->normalizeConstraintName($this->name),
            $columns,
        );
    }
}
