<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class CompanyAddressOutput
{
    public function __construct(
        #[Groups(['me:read'])]
        public string $streetLine1,
        #[Groups(['me:read'])]
        public ?string $streetLine2,
        #[Groups(['me:read'])]
        public string $postalCode,
        #[Groups(['me:read'])]
        public string $city,
        #[Groups(['me:read'])]
        public ?string $region,
        #[Groups(['me:read'])]
        public string $countryCode,
    ) {
    }
}
