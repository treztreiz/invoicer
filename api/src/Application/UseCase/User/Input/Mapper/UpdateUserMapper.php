<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input\Mapper;

use App\Application\UseCase\User\Input\CompanyAddressInput;
use App\Application\UseCase\User\Input\CompanyInput;
use App\Application\UseCase\User\Input\UserInput;
use App\Domain\DTO\UserUpdateProfilePayload;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\CompanyLogo;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\VatRate;

final class UpdateUserMapper
{
    public function map(UserInput $input, CompanyLogo $logo): UserUpdateProfilePayload
    {
        return new UserUpdateProfilePayload(
            name: new Name($input->firstName, $input->lastName),
            contact: new Contact($input->email, $input->phone),
            company: $this->mapCompany($input->company, $logo),
            locale: $input->locale,
            userIdentifier: $input->email,
        );
    }

    private function mapCompany(CompanyInput $companyInput, CompanyLogo $logo): Company
    {
        return new Company(
            legalName: $companyInput->legalName,
            logo: $logo,
            contact: new Contact($companyInput->email, $companyInput->phone),
            address: $this->mapAddress($companyInput->address),
            defaultCurrency: $companyInput->defaultCurrency,
            defaultHourlyRate: new Money($companyInput->defaultHourlyRate),
            defaultDailyRate: new Money($companyInput->defaultDailyRate),
            defaultVatRate: new VatRate($companyInput->defaultVatRate),
            legalMention: $companyInput->legalMention,
        );
    }

    private function mapAddress(CompanyAddressInput $address): Address
    {
        return new Address(
            streetLine1: $address->streetLine1,
            streetLine2: $address->streetLine2,
            postalCode: $address->postalCode,
            city: $address->city,
            region: $address->region,
            countryCode: $address->countryCode,
        );
    }
}
