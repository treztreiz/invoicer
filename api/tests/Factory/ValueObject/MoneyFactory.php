<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\Money;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Money>
 */
final class MoneyFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Money::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'value' => (string) self::faker()->numberBetween(1, 100),
        ];
    }
}
