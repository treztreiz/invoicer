<?php

declare(strict_types=1);

namespace App\Tests\Factory\User;

use App\Domain\Entity\User\User;
use App\Tests\Factory\ValueObject\CompanyFactory;
use App\Tests\Factory\ValueObject\ContactFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentObjectFactory<User>
 */
class UserFactory extends PersistentObjectFactory
{
    public const string HASHED_PASSWORD = '$2y$13$L0AelBL1YUFZol8T9ROKk.W6lqZ2CX162s50Xj.1mbpHhfff7P8nK';
    public const string PLAIN_PASSWORD = 'Password123!';

    #[\Override]
    public static function class(): string
    {
        return User::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'name' => NameFactory::new(),
            'contact' => ContactFactory::new(),
            'company' => CompanyFactory::new(),
            'userIdentifier' => self::faker()->email(),
            'roles' => [],
            'password' => self::HASHED_PASSWORD,
            'locale' => self::faker()->languageCode(),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function build(array $attributes = []): User
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
