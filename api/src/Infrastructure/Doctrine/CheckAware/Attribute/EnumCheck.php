<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\CheckAware\Attribute;

use App\Infrastructure\Doctrine\CheckAware\Enum\ConstraintTiming;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final readonly class EnumCheck
{
    public function __construct(
        public string $property,
        public string $name = 'ENUM_CHECK',
        public ?string $enumFqcn = null,
        public ConstraintTiming $timing = ConstraintTiming::IMMEDIATE,
    ) {
    }
}


