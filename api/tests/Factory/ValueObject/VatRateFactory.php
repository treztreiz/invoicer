<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\VatRate;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<VatRate>
 */
final class VatRateFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return VatRate::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'value' => (string) self::faker()->numberBetween(0, 100),
        ];
    }
}
