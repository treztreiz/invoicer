<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input\Mapper;

use App\Application\UseCase\Customer\Input\CustomerAddressInput;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Domain\Entity\Customer\Customer;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final class CreateCustomerMapper
{
    public function map(CustomerInput $input): Customer
    {
        return new Customer(
            name: new Name($input->firstName, $input->lastName),
            contact: new Contact($input->email, $input->phone),
            address: $this->mapAddress($input->address),
        );
    }

    public static function mapAddress(CustomerAddressInput $addressInput): Address
    {
        return new Address(
            streetLine1: $addressInput->streetLine1,
            streetLine2: $addressInput->streetLine2,
            postalCode: $addressInput->postalCode,
            city: $addressInput->city,
            region: $addressInput->region,
            countryCode: $addressInput->countryCode,
        );
    }
}
