<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Document\Document;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\ValueObject\AmountBreakdownFactory;
use App\Tests\Factory\ValueObject\VatRateFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @template T of Document
 *
 * @extends PersistentObjectFactory<T>
 */
abstract class DocumentFactory extends PersistentObjectFactory
{
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'title' => static::faker()->title(),
            'currency' => static::faker()->currencyCode(),
            'vatRate' => VatRateFactory::new(),
            'total' => AmountBreakdownFactory::new(),
            'customer' => CustomerFactory::new(),
            'customerSnapshot' => [],
            'companySnapshot' => [],
        ];
    }

    public function withLines(int $numberOfLines): static
    {
        $lines = DocumentLineFactory::build([
            'document' => $this,
        ])->many($numberOfLines);

        return $this->with(['lines' => $lines]);
    }
}
