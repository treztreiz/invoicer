<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output\Mapper;

use App\Application\UseCase\User\Output\CompanyAddressOutput;
use App\Application\UseCase\User\Output\CompanyOutput;
use App\Application\UseCase\User\Output\UserOutput;
use App\Domain\Entity\User\User;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;

final class UserOutputMapper
{
    public function map(User $user): UserOutput
    {
        return new UserOutput(
            customerId: $user->id->toRfc4122(),
            firstName: $user->name->firstName,
            lastName: $user->name->lastName,
            email: $user->contact->email ?? $user->userIdentifier,
            phone: $user->contact->phone,
            locale: $user->locale,
            roles: $user->roles,
            company: $this->mapCompany($user->company),
        );
    }

    private function mapCompany(Company $company): CompanyOutput
    {
        return new CompanyOutput(
            legalName: $company->legalName,
            email: $company->contact->email,
            phone: $company->contact->phone,
            address: $this->mapCompanyAddress($company->address),
            defaultCurrency: $company->defaultCurrency,
            defaultHourlyRate: $company->defaultHourlyRate->value,
            defaultDailyRate: $company->defaultDailyRate->value,
            defaultVatRate: $company->defaultVatRate->value,
            legalMention: $company->legalMention,
            logoUrl: $company->logo->url()
        );
    }

    private function mapCompanyAddress(Address $address): CompanyAddressOutput
    {
        return new CompanyAddressOutput(
            streetLine1: $address->streetLine1,
            streetLine2: $address->streetLine2,
            postalCode: $address->postalCode,
            city: $address->city,
            region: $address->region,
            countryCode: $address->countryCode
        );
    }
}
