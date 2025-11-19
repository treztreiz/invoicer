<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Output;

use App\Application\Dto\Address\Output\AddressOutput;
use App\Application\Dto\Address\Output\AddressOutputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\ValueObject\Company;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: Company::class)]
final readonly class CompanyOutput
{
    public function __construct(
        private(set) string $legalName,

        #[Map(source: 'contact.email')]
        private(set) ?string $email,

        #[Map(source: 'contact.phone')]
        private(set) ?string $phone,

        #[Map(transform: AddressOutputTransformer::class)]
        private(set) AddressOutput $address,

        private(set) string $defaultCurrency,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $defaultHourlyRate,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $defaultDailyRate,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $defaultVatRate,

        private(set) ?string $legalMention,
    ) {
    }
}
