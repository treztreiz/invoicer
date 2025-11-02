<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Contracts;

use App\Infrastructure\Doctrine\CheckAware\Schema\Service\CheckNormalizer;

interface NormalizableCheckSpecInterface extends CheckSpecInterface
{
    public function normalizeWith(CheckNormalizer $normalizer): self;

    public function isNormalized(): bool;
}
