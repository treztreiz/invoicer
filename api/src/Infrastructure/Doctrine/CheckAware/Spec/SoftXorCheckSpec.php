<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;

final readonly class SoftXorCheckSpec implements CheckSpecInterface
{
    /** @param array{cols: non-empty-list<string>} $expr */
    public function __construct(
        private(set) string $name,
        private(set) array $expr,
        private(set) bool $deferrable = false,
    ) {
    }
}
