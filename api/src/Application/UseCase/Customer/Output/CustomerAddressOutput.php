<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class CustomerAddressOutput
{
    public function __construct(
        #[Groups(['customer:read'])]
        public string $streetLine1,

        #[Groups(['customer:read'])]
        public ?string $streetLine2,

        #[Groups(['customer:read'])]
        public string $postalCode,

        #[Groups(['customer:read'])]
        public string $city,

        #[Groups(['customer:read'])]
        public ?string $region,

        #[Groups(['customer:read'])]
        public string $countryCode,
    ) {
    }
}
