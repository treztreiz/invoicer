<?php

declare(strict_types=1);

namespace App\Tests\Factory\Customer;

use App\Domain\Entity\Customer\Customer;
use App\Tests\Factory\ValueObject\AddressFactory;
use App\Tests\Factory\ValueObject\ContactFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

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
            'name' => NameFactory::new(),
            'contact' => ContactFactory::new(),
            'address' => AddressFactory::new(),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function build(array $attributes = []): Customer
    {
        $entity = self::new($attributes)
            ->withoutPersisting()
            ->create();

        if ($entity instanceof Proxy) {
            return $entity->_real();
        }

        return $entity;
    }
}
