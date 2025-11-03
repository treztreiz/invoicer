<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Attribute;

use App\Infrastructure\Doctrine\CheckAware\Enum\ConstraintTiming;

#[\Attribute(\Attribute::TARGET_CLASS)]
class SoftXorCheck
{
    /**
     * @param non-empty-list<string> $properties Doctrine-mapped properties (e.g. ['a','b','c'])
     * @param string $name Stable DB constraint name (idempotency)
     */
    public function __construct(
        public array $properties,
        public string $name = 'SOFT_XOR',
        public ConstraintTiming $timing = ConstraintTiming::IMMEDIATE,
    ) {
    }
}

