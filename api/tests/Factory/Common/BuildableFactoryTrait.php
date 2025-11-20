<?php

declare(strict_types=1);

namespace App\Tests\Factory\Common;

use Zenstruck\Foundry\Object\Instantiator;

trait BuildableFactoryTrait
{
    public static function build(array|callable $attributes = [], bool $forcePrivateProperties = true): static
    {
        $instantiator = Instantiator::withoutConstructor();
        if ($forcePrivateProperties) {
            $instantiator = $instantiator->alwaysForce();
        }

        return static::new($attributes)->instantiateWith($instantiator);
    }
}
