<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyAddressInput
{
    public function __construct(
        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $streetLine1,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $postalCode,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $city,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $countryCode,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 255)]
        public ?string $streetLine2 = null,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 150)]
        public ?string $region = null,
    ) {
    }
}
