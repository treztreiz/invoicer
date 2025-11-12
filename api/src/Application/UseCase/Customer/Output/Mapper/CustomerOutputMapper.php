<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Output\Mapper;

use App\Application\UseCase\Customer\Output\CustomerAddressOutput;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Domain\Entity\Customer\Customer;
use App\Domain\ValueObject\Address;

final class CustomerOutputMapper
{
    public function map(Customer $customer): CustomerOutput
    {
        return new CustomerOutput(
            customerId: $customer->id?->toRfc4122() ?? '',
            firstName: $customer->name->firstName,
            lastName: $customer->name->lastName,
            email: $customer->contact->email ?? '',
            phone: $customer->contact->phone,
            isArchived: $customer->isArchived,
            address: $this->mapAddress($customer->address),
        );
    }

    private function mapAddress(Address $address): CustomerAddressOutput
    {
        return new CustomerAddressOutput(
            streetLine1: $address->streetLine1,
            streetLine2: $address->streetLine2,
            postalCode: $address->postalCode,
            city: $address->city,
            region: $address->region,
            countryCode: $address->countryCode,
        );
    }
}
