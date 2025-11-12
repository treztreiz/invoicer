<?php

declare(strict_types=1);

namespace App\Tests\Factory\Customer;

use App\Domain\Entity\Customer\Customer;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use App\Tests\Factory\ValueObject\AddressFactory;
use App\Tests\Factory\ValueObject\ContactFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Customer>
 */
final class CustomerFactory extends PersistentObjectFactory
{
    use BuildableFactoryTrait;

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

    public function archived(): self
    {
        return $this->with(['isArchived' => true]);
    }
}
