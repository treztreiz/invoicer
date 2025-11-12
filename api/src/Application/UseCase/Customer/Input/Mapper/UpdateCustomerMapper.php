<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input\Mapper;

use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Domain\DTO\CustomerPayload;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final class UpdateCustomerMapper
{
    public function map(CustomerInput $input): CustomerPayload
    {
        return new CustomerPayload(
            name: new Name($input->firstName, $input->lastName),
            contact: new Contact($input->email, $input->phone),
            address: CreateCustomerMapper::mapAddress($input->address),
        );
    }
}
