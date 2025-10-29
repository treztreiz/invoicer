<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class SoftXor
{
    /**
     * @param non-empty-list<string> $properties Doctrine-mapped properties (e.g. ['a','b','c'])
     * @param string                 $name       Stable DB constraint name (idempotency)
     */
    public function __construct(
        public array $properties,
        public string $name = 'SOFT_XOR',
    ) {
        if (count($this->properties) < 2) {
            throw new \LogicException('SoftXor requires at least 2 properties.');
        }
    }
}
