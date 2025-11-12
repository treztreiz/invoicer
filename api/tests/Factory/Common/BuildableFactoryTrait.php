<?php

declare(strict_types=1);

namespace App\Tests\Factory\Common;

use Zenstruck\Foundry\Object\Instantiator;

trait BuildableFactoryTrait
{
    public static function build(array|callable $attributes = [], bool $forcePrivateProperties = true): static
    {
        $factory = static::new($attributes);

        if ($forcePrivateProperties) {
            $factory = $factory->instantiateWith(Instantiator::withConstructor()->alwaysForce());
        }

        return $factory;
    }
}
