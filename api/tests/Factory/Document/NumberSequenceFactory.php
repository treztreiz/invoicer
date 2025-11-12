<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<NumberSequence> */
class NumberSequenceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return NumberSequence::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'documentType' => self::faker()->randomElement(DocumentType::cases()),
            'year' => self::faker()->year(),
        ];
    }
}
