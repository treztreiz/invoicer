<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\Address;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Address>
 */
final class AddressFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Address::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'streetLine1' => self::faker()->streetAddress(),
            'streetLine2' => self::faker()->streetAddress(),
            'postalCode' => self::faker()->postcode(),
            'city' => self::faker()->city(),
            'region' => self::faker()->word(),
            'countryCode' => self::faker()->countryCode(),
        ];
    }
}
