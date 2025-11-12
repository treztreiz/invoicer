<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Address;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class AddressTest extends TestCase
{
    public function test_accepts_when_all_fields_valid(): void
    {
        $address = static::createAddress();

        static::assertSame('1 rue Test', $address->streetLine1);
        static::assertSame('FR', $address->countryCode);
    }

    public function test_blank_street_line_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Address(
            streetLine1: '   ',
            streetLine2: null,
            postalCode: '75000',
            city: 'Paris',
            region: null,
            countryCode: 'FR'
        );
    }

    public function test_invalid_country_code_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Address(
            streetLine1: '1 rue Test',
            streetLine2: null,
            postalCode: '75000',
            city: 'Paris',
            region: null,
            countryCode: 'FRA'
        );
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function createAddress(): Address
    {
        return new Address(
            streetLine1: '1 rue Test',
            streetLine2: null,
            postalCode: '75000',
            city: 'Paris',
            region: null,
            countryCode: 'fr'
        );
    }
}
