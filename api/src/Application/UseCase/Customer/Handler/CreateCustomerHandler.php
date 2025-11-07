<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Handler;

use App\Application\Contract\UseCaseHandlerInterface;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final readonly class CreateCustomerHandler implements UseCaseHandlerInterface
{
    public function __construct(private CustomerRepositoryInterface $customerRepository)
    {
    }

    public function handle(object $input): Customer
    {
        if (!$input instanceof CustomerInput) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', CustomerInput::class, $input::class));
        }

        $addressInput = $input->address;
        $address = new Address(
            streetLine1: $addressInput->streetLine1,
            streetLine2: $addressInput->streetLine2,
            postalCode: $addressInput->postalCode,
            city: $addressInput->city,
            region: $addressInput->region,
            countryCode: $addressInput->countryCode,
        );

        $customer = new Customer(
            name: new Name($input->firstName, $input->lastName),
            contact: new Contact($input->email, $input->phone),
            address: $address,
        );

        $this->customerRepository->save($customer);

        return $customer;
    }
}
