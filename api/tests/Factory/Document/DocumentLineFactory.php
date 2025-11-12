<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Document\DocumentLine;
use App\Domain\Enum\RateUnit;
use App\Tests\Factory\ValueObject\AmountBreakdownFactory;
use App\Tests\Factory\ValueObject\MoneyFactory;
use App\Tests\Factory\ValueObject\QuantityFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DocumentLine>
 */
class DocumentLineFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return DocumentLine::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'description' => self::faker()->text(),
            'quantity' => QuantityFactory::new(),
            'rateUnit' => self::faker()->randomElement(RateUnit::cases()),
            'rate' => MoneyFactory::new(),
            'amount' => AmountBreakdownFactory::new(),
            'position' => 0,
        ];
    }
}
