<?php

declare(strict_types=1);

namespace App\Application\Dto\Customer\Output;

use App\Application\Dto\Address\Output\AddressOutput;
use App\Application\Dto\Address\Output\AddressOutputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Customer\Customer;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: Customer::class)]
final readonly class CustomerOutput
{
    public function __construct(
        #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $customerId,

        private(set) ?string $legalName,

        #[Map(source: 'name.firstName')]
        private(set) string $firstName,

        #[Map(source: 'name.lastName')]
        private(set) string $lastName,

        #[Map(source: 'contact.email')]
        private(set) ?string $email,

        #[Map(source: 'contact.phone')]
        private(set) ?string $phone,

        #[Map(transform: AddressOutputTransformer::class)]
        private(set) AddressOutput $address,

        #[Map(transform: [OutputTransformer::class, 'dateTime'])]
        private(set) string $createdAt,

        #[Map(source: 'isArchived')]
        private(set) bool $archived,
    ) {
    }
}
