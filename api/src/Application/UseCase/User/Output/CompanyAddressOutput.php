<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class CompanyAddressOutput
{
    public function __construct(
        #[Groups(['user:read'])]
        private(set) string $streetLine1,
        #[Groups(['user:read'])]
        private(set) ?string $streetLine2,
        #[Groups(['user:read'])]
        private(set) string $postalCode,
        #[Groups(['user:read'])]
        private(set) string $city,
        #[Groups(['user:read'])]
        private(set) ?string $region,
        #[Groups(['user:read'])]
        private(set) string $countryCode,
    ) {
    }
}
