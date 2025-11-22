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
            'title' => static::faker()->word(),
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
        return $this->with([
            'lines' => DocumentLineFactory::build([
                'document' => $this,
            ])->sequence(static function () use ($numberOfLines) {
                for ($i = 0; $i < $numberOfLines; ++$i) {
                    yield ['position' => $i];
                }
            }),
        ]);
    }
}
