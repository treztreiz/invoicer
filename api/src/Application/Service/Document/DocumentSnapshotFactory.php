<?php

declare(strict_types=1);

namespace App\Application\Service\Document;

use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Document;
use App\Domain\Entity\User\User;

/**
 * @phpstan-import-type CustomerSnapshot from Document
 * @phpstan-import-type CompanySnapshot from Document
 */
final class DocumentSnapshotFactory
{
    /**
     * @return CustomerSnapshot
     */
    public function customerSnapshot(Customer $customer): array
    {
        return [
            'id' => $customer->id?->toRfc4122(),
            'name' => [
                'first' => $customer->name->firstName,
                'last' => $customer->name->lastName,
            ],
            'contact' => [
                'email' => $customer->contact->email,
                'phone' => $customer->contact->phone,
            ],
            'address' => [
                'streetLine1' => $customer->address->streetLine1,
                'streetLine2' => $customer->address->streetLine2,
                'postalCode' => $customer->address->postalCode,
                'city' => $customer->address->city,
                'region' => $customer->address->region,
                'countryCode' => $customer->address->countryCode,
            ],
        ];
    }

    /**
     * @return CompanySnapshot
     */
    public function companySnapshot(User $user): array
    {
        $company = $user->company;

        return [
            'legalName' => $company->legalName,
            'contact' => [
                'email' => $company->contact->email,
                'phone' => $company->contact->phone,
            ],
            'address' => [
                'streetLine1' => $company->address->streetLine1,
                'streetLine2' => $company->address->streetLine2,
                'postalCode' => $company->address->postalCode,
                'city' => $company->address->city,
                'region' => $company->address->region,
                'countryCode' => $company->address->countryCode,
            ],
            'defaultCurrency' => $company->defaultCurrency,
            'defaultHourlyRate' => $company->defaultHourlyRate->value,
            'defaultDailyRate' => $company->defaultDailyRate->value,
            'defaultVatRate' => $company->defaultVatRate->value,
            'legalMention' => $company->legalMention,
        ];
    }
}
