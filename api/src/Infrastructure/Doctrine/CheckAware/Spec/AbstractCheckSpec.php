<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;
use App\Infrastructure\Doctrine\CheckAware\Contracts\NormalizableCheckSpecInterface;

abstract class AbstractCheckSpec implements CheckSpecInterface, NormalizableCheckSpecInterface
{
    protected bool $normalized = false;

    public function isNormalized(): bool
    {
        return $this->normalized;
    }

    public function canonicalExpression(): string
    {
        if (!$this->normalized) {
            throw new \LogicException(sprintf('%s must be normalized before building canonical expression.', static::class));
        }

        return $this->buildCanonicalExpression();
    }

    protected static function fromNormalized(string $name, array $expr, bool $deferrable): static
    {
        $spec = new static($name, $expr, $deferrable);
        $spec->normalized = true;

        return $spec;
    }
}
