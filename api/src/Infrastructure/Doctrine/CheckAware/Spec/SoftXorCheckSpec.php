<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

final class SoftXorCheckSpec extends AbstractCheckSpec
{
    /** @param array{columns: list<string>} $expr */
    public function __construct(
        private(set) readonly string $name,
        private(set) readonly array $expr,
        private(set) readonly bool $deferrable = false,
    ) {
        if (!isset($this->expr['columns']) || !is_array($this->expr['columns']) || count($this->expr['columns']) < 2) {
            throw new \InvalidArgumentException('SoftXorCheckSpec requires at least two columns.');
        }
    }

    public function normalizeWith(CheckNormalizer $normalizer): self
    {
        if ($this->normalized) {
            return $this;
        }

        $cols = array_map([$normalizer, 'normalizeIdentifier'], $this->expr['columns']);

        return self::fromNormalized(
            $normalizer->normalizeConstraintName($this->name),
            ['columns' => $cols],
            $this->deferrable,
        );
    }
}
