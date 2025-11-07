<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Mapper;

use App\Application\Contract\OutputMapperInterface;
use App\Application\UseCase\Customer\Output\CustomerAddressOutput;
use App\Application\UseCase\Customer\Output\CustomerOutput;
use App\Domain\Entity\Customer\Customer;

final class CustomerOutputMapper implements OutputMapperInterface
{
    public function toOutput(object $model): CustomerOutput
    {
        if (!$model instanceof Customer) {
            throw new \InvalidArgumentException(sprintf('Expected %s, got %s.', Customer::class, $model::class));
        }

        return new CustomerOutput(
            id: $model->id?->toRfc4122() ?? '',
            firstName: $model->name->firstName,
            lastName: $model->name->lastName,
            email: $model->contact->email ?? '',
            phone: $model->contact->phone,
            isArchived: $model->isArchived,
            address: new CustomerAddressOutput(
                streetLine1: $model->address->streetLine1,
                streetLine2: $model->address->streetLine2,
                postalCode: $model->address->postalCode,
                city: $model->address->city,
                region: $model->address->region,
                countryCode: $model->address->countryCode,
            ),
        );
    }
}
