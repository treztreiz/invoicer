<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Tests\Factory\ValueObject\AmountBreakdownFactory;
use App\Tests\Factory\ValueObject\VatRateFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

abstract class DocumentFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'title' => static::faker()->title(),
            'currency' => static::faker()->currencyCode(),
            'vatRate' => VatRateFactory::new(),
            'total' => AmountBreakdownFactory::new(),
            'customerSnapshot' => [],
            'companySnapshot' => [],
        ];
    }
}
