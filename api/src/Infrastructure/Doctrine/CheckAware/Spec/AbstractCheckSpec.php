<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

abstract class AbstractCheckSpec implements CheckSpecInterface
{
    protected(set) bool $normalized = false;

    public function __construct(
        protected(set) readonly string $name,
        protected(set) readonly bool $deferrable = false,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException(sprintf('%s name cannot be empty.', static::class));
        }
    }

    public function normalizeWith(CheckNormalizer $normalizer): static
    {
        if ($this->normalized) {
            return $this;
        }

        $normalizedSpec = $this->normalize($normalizer);
        $normalizedSpec->normalized = true;

        return $normalizedSpec;
    }

    abstract protected function normalize(CheckNormalizer $normalizer): self;
}
