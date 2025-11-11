<?php

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\Contact;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Contact>
 */
final class ContactFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Contact::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
        ];
    }
}
