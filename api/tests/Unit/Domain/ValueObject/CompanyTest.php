<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ValueObject;

use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\CompanyLogo;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VatRate;
use PHPUnit\Framework\TestCase;

/**
 * @testType solitary-unit
 */
final class CompanyTest extends TestCase
{
    public function test_construct_accepts_valid_values(): void
    {
        $company = new Company(
            legalName: 'Acme Corp',
            logo: CompanyLogo::empty(),
            contact: new Contact('info@example.com', '+33123456789'),
            address: AddressTest::createAddress(),
            defaultCurrency: 'eur',
            defaultHourlyRate: new Money('100.00'),
            defaultDailyRate: new Money('800.00'),
            defaultVatRate: new VatRate('20.00'),
            legalMention: '  VAT FR 123  ',
        );

        static::assertSame('Acme Corp', $company->legalName);
        static::assertSame('EUR', $company->defaultCurrency);
        static::assertSame('VAT FR 123', $company->legalMention);
    }

    public function test_blank_legal_name_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Company(
            legalName: '   ',
            logo: CompanyLogo::empty(),
            contact: new Contact('info@example.com', '+33123456789'),
            address: AddressTest::createAddress(),
            defaultCurrency: 'EUR',
            defaultHourlyRate: new Money('100.00'),
            defaultDailyRate: new Money('800.00'),
            defaultVatRate: new VatRate('20.00'),
            legalMention: null,
        );
    }

    public function test_invalid_currency_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Company(
            legalName: 'Acme Corp',
            logo: CompanyLogo::empty(),
            contact: new Contact('info@example.com', '+33123456789'),
            address: AddressTest::createAddress(),
            defaultCurrency: 'EURO',
            defaultHourlyRate: new Money('100.00'),
            defaultDailyRate: new Money('800.00'),
            defaultVatRate: new VatRate('20.00'),
            legalMention: null,
        );
    }
}
