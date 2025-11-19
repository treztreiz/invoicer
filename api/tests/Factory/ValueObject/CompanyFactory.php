<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\Company;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<Company>
 */
final class CompanyFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Company::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'legalName' => self::faker()->company(),
            'contact' => ContactFactory::new(),
            'address' => AddressFactory::new(),
            'defaultCurrency' => self::faker()->currencyCode(),
            'defaultHourlyRate' => MoneyFactory::new(),
            'defaultDailyRate' => MoneyFactory::new(),
            'defaultVatRate' => VatRateFactory::new(),
        ];
    }
}
