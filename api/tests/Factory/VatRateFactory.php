<?php

namespace App\Tests\Factory;

use App\Domain\ValueObject\VatRate;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<VatRate>
 */
final class VatRateFactory extends ObjectFactory{
    #[\Override]    public static function class(): string
    {
        return VatRate::class;
    }

    #[\Override]    protected function defaults(): array    {
        return [
            'value' => self::faker()->sentence(),
        ];
    }

}
