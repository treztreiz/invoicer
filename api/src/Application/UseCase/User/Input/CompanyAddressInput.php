<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CompanyAddressInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private(set) string $streetLine1,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        private(set) string $postalCode,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        private(set) string $city,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        private(set) string $countryCode,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 255)]
        private(set) ?string $streetLine2 = null,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 150)]
        private(set) ?string $region = null,
    ) {
    }
}
