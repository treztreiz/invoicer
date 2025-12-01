<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Invoice;

use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice\Invoice;
use App\Domain\Enum\InvoiceStatus;
use App\Domain\ValueObject\Company;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use App\Tests\Factory\Document\DocumentFactory;
use Symfony\Component\Uid\Uuid;

/** @extends DocumentFactory<Invoice> */
class InvoiceFactory extends DocumentFactory
{
    use BuildableFactoryTrait;

    #[\Override]
    public static function class(): string
    {
        return Invoice::class;
    }

    public function draft(): self
    {
        return $this->with(['status' => InvoiceStatus::DRAFT]);
    }

    public function issued(): self
    {
        return $this->with(['status' => InvoiceStatus::ISSUED]);
    }

    public function overdue(): self
    {
        return $this->with(['status' => InvoiceStatus::OVERDUE]);
    }

    public function paid(): self
    {
        return $this->with(['status' => InvoiceStatus::PAID]);
    }

    public function voided(): self
    {
        return $this->with(['status' => InvoiceStatus::VOIDED]);
    }

    public function withRecurrence(array $attributes = []): self
    {
        $default = ['nextRunAt' => new \DateTimeImmutable('tomorrow')];
        
        return $this->with([
            'recurrence' => RecurrenceFactory::build([
                ...$default,
                ...$attributes,
            ]),
        ]);
    }

    public function withInstallmentPlan(int $numberOfInstallments = 0): self
    {
        $installmentPlan = InstallmentPlanFactory::build();
        $installments = InstallmentFactory::build([
            'installmentPlan' => $installmentPlan,
        ])->sequence(static function () use ($numberOfInstallments) {
            for ($i = 0; $i < $numberOfInstallments; ++$i) {
                yield ['position' => $i];
            }
        });

        return $this->with([
            'installmentPlan' => $installmentPlan->with([
                'installments' => $installments,
            ]),
        ]);
    }

    public function withSnapshots(?Customer $customer = null, ?Company $company = null): self
    {
        $customerSnapshot = [
            'id' => self::faker()->uuid,
            'legalName' => $customer?->legalName ?: self::faker()->company,
            'name' => [
                'first' => $customer?->name->firstName ?: self::faker()->firstName,
                'last' => $customer?->name->lastName ?: self::faker()->lastName,
            ],
            'contact' => [
                'email' => $customer?->contact->email ?: self::faker()->email,
                'phone' => $customer?->contact->phone ?: self::faker()->phoneNumber,
            ],
            'address' => [
                'streetLine1' => $customer?->address->streetLine1 ?: self::faker()->address,
                'streetLine2' => $customer?->address->streetLine2 ?: self::faker()->address,
                'postalCode' => $customer?->address->postalCode ?: self::faker()->postcode,
                'city' => $customer?->address->city ?: self::faker()->city,
                'region' => $customer?->address->region,
                'countryCode' => $customer?->address->countryCode ?: self::faker()->countryCode,
            ],
        ];

        $companySnapshot = [
            'legalName' => $company?->legalName ?: self::faker()->company,
            'contact' => [
                'email' => $company?->contact->email ?: self::faker()->email,
                'phone' => $company?->contact->phone ?: self::faker()->phoneNumber,
            ],
            'address' => [
                'streetLine1' => $company?->address->streetLine1 ?: self::faker()->address,
                'streetLine2' => $company?->address->streetLine2 ?: self::faker()->address,
                'postalCode' => $company?->address->postalCode ?: self::faker()->postcode,
                'city' => $company?->address->city ?: self::faker()->city,
                'region' => $company?->address->region,
                'countryCode' => $company?->address->countryCode ?: self::faker()->countryCode,
            ],
            'defaultCurrency' => $company?->defaultCurrency ?: self::faker()->currencyCode(),
            'defaultHourlyRate' => $company?->defaultHourlyRate->value ?: '10.00',
            'defaultDailyRate' => $company?->defaultDailyRate->value ?: '10.00',
            'defaultVatRate' => $company?->defaultVatRate->value ?: '10.00',
            'legalMention' => $company?->legalMention,
        ];

        return $this->with([
            'customerSnapshot' => $customerSnapshot,
            'companySnapshot' => $companySnapshot,
        ]);
    }

    public function generatedFromRecurrence(): self
    {
        return $this->with(['recurrenceSeedId' => Uuid::v7()]);
    }

    public function generatedFromInstallment(): self
    {
        return $this->with(['installmentSeedId' => Uuid::v7()]);
    }
}
