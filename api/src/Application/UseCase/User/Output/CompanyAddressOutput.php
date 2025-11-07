<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class CompanyAddressOutput
{
    public function __construct(
        #[Groups(['user:read'])]
        public string $streetLine1,
        #[Groups(['user:read'])]
        public ?string $streetLine2,
        #[Groups(['user:read'])]
        public string $postalCode,
        #[Groups(['user:read'])]
        public string $city,
        #[Groups(['user:read'])]
        public ?string $region,
        #[Groups(['user:read'])]
        public string $countryCode,
    ) {
    }
}
