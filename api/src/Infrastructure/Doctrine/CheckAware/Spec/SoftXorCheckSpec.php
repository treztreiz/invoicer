<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

final class SoftXorCheckSpec extends AbstractCheckSpec
{
    /** @param array{cols: non-empty-list<string>} $expr */
    public function __construct(
        private(set) readonly string $name,
        private(set) readonly array $expr,
        private(set) readonly bool $deferrable = false,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('SoftXorCheckSpec name cannot be empty.');
        }

        if (!isset($this->expr['cols']) || !is_array($this->expr['cols']) || [] === $this->expr['cols']) {
            throw new \InvalidArgumentException('SoftXorCheckSpec requires at least one column.');
        }
    }

    public function normalizeWith(CheckNormalizer $normalizer): self
    {
        if ($this->normalized) {
            return $this;
        }

        $cols = array_map([$normalizer, 'normalizeIdentifier'], $this->expr['cols']);

        return self::fromNormalized(
            $normalizer->normalizeConstraintName($this->name),
            ['cols' => $cols],
            $this->deferrable,
        );
    }
}
