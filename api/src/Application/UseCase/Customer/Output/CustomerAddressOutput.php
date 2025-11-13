<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class CustomerAddressOutput
{
    public function __construct(
        #[Groups(['customer:read'])]
        private(set) string $streetLine1,
        #[Groups(['customer:read'])]
        private(set) ?string $streetLine2,
        #[Groups(['customer:read'])]
        private(set) string $postalCode,
        #[Groups(['customer:read'])]
        private(set) string $city,
        #[Groups(['customer:read'])]
        private(set) ?string $region,
        #[Groups(['customer:read'])]
        private(set) string $countryCode,
    ) {
    }
}
