<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Command;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyAddressCommand
{
    public function __construct(
        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $streetLine1,

        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $postalCode,

        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $city,

        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $countryCode,

        #[Groups(['me:write'])]
        #[Assert\Length(max: 255)]
        public ?string $streetLine2 = null,

        #[Groups(['me:write'])]
        #[Assert\Length(max: 150)]
        public ?string $region = null,
    ) {
    }
}
