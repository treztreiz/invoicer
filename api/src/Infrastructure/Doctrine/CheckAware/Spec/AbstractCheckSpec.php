<?php

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

    protected static function fromNormalized(string $name, array $expr, bool $deferrable): static
    {
        $spec = new static($name, $expr, $deferrable);
        $spec->normalized = true;

        return $spec;
    }
}