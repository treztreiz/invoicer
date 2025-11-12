<?php

namespace App\Tests\Factory\Common;

use Zenstruck\Foundry\Object\Instantiator;

trait BuildableFactoryTrait
{
    public static function build(array|callable $attributes = [], bool $forcePrivateProperties = true): static
    {
        $factory = self::new($attributes);

        if ($forcePrivateProperties) {
            $factory = $factory->instantiateWith(Instantiator::withConstructor()->alwaysForce());
        }

        return $factory->withoutPersisting();
    }
}