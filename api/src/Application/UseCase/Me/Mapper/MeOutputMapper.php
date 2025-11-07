<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Mapper;

use App\Application\Contract\OutputMapperInterface;
use App\Application\UseCase\Me\Output\CompanyAddressOutput;
use App\Application\UseCase\Me\Output\CompanyOutput;
use App\Application\UseCase\Me\Output\MeOutput;
use App\Domain\Entity\User\User;

final class MeOutputMapper implements OutputMapperInterface
{
    public function toOutput(object $model): MeOutput
    {
        if (!$model instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', User::class, $model::class));
        }

        $user = $model;
        $name = $user->name;
        $contact = $user->contact;
        $company = $user->company;
        $companyContact = $company->contact();
        $address = $company->address();
        $logo = $user->logo;

        $companyOutput = new CompanyOutput(
            legalName: $company->legalName,
            email: $companyContact->email,
            phone: $companyContact->phone,
            address: new CompanyAddressOutput(
                streetLine1: $address->streetLine1,
                streetLine2: $address->streetLine2,
                postalCode: $address->postalCode,
                city: $address->city,
                region: $address->region,
                countryCode: $address->countryCode
            ),
            defaultCurrency: $company->defaultCurrency,
            defaultHourlyRate: $company->defaultHourlyRate->value(),
            defaultDailyRate: $company->defaultDailyRate->value(),
            defaultVatRate: $company->defaultVatRate->value(),
            legalMention: $company->legalMention,
            logoUrl: $logo->url()
        );

        return new MeOutput(
            id: $user->id->toRfc4122(),
            firstName: $name->firstName,
            lastName: $name->lastName,
            email: $contact->email ?? $user->userIdentifier,
            phone: $contact->phone,
            locale: $user->locale,
            roles: $user->roles,
            company: $companyOutput,
        );
    }
}
