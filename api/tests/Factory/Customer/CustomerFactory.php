<?php

namespace App\Tests\Factory\Customer;

use App\Domain\Entity\Customer\Customer;
use App\Tests\Factory\ValueObject\AddressFactory;
use App\Tests\Factory\ValueObject\ContactFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Customer>
 */
final class CustomerFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Customer::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'address' => AddressFactory::new(),
            'contact' => ContactFactory::new(),
            'name' => NameFactory::new(),
        ];
    }
}
