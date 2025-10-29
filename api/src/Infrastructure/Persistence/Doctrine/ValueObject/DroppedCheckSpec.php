<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\ValueObject;

use App\Infrastructure\Persistence\Doctrine\Contracts\CheckSpecInterface;

/** @note Only used for DROP; expr/deferrable are irrelevant. */
final class DroppedCheckSpec implements CheckSpecInterface
{
    /** @var array{} */
    private(set) array $expr = [];

    private(set) bool $deferrable = false;

    public function __construct(
        private(set) readonly string $name,
    ) {
    }
}
