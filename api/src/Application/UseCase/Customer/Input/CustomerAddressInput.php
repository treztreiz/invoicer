<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomerAddressInput
{
    public function __construct(
        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $streetLine1,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $postalCode,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $city,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $countryCode,

        #[Groups(['customer:write'])]
        #[Assert\Length(max: 255)]
        public ?string $streetLine2 = null,

        #[Groups(['customer:write'])]
        #[Assert\Length(max: 150)]
        public ?string $region = null,
    ) {
    }
}
