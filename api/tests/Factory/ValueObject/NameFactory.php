<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\Name;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Name>
 */
final class NameFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Name::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
        ];
    }
}
