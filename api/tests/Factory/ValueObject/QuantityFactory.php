<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\Quantity;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Quantity>
 */
final class QuantityFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Quantity::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'value' => (string) self::faker()->numberBetween(1, 100),
        ];
    }
}
