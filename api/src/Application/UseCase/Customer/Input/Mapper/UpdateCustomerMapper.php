<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input\Mapper;

use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Domain\Entity\Customer\Customer;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final class UpdateCustomerMapper
{
    public function map(Customer $customer, CustomerInput $input): Customer
    {
        $customer->name = new Name($input->firstName, $input->lastName);
        $customer->contact = new Contact($input->email, $input->phone);
        $customer->address = CreateCustomerMapper::mapAddress($input->address);

        return $customer;
    }
}
