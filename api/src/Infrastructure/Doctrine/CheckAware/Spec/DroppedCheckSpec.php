<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Spec;

use App\Infrastructure\Doctrine\CheckAware\Contracts\CheckSpecInterface;

/** @note Only used for DROP; expression/timing are irrelevant. */
final readonly class DroppedCheckSpec implements CheckSpecInterface
{
    public function __construct(
        private(set) string $name,
    ) {
        if ('' === trim($this->name)) {
            throw new \InvalidArgumentException('DroppedCheckSpec name cannot be empty.');
        }
    }
}
