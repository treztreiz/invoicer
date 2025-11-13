<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CustomerAddressInput
{
    public function __construct(
        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private(set) string $streetLine1,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        private(set) string $postalCode,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        private(set) string $city,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Country]
        private(set) string $countryCode,

        #[Groups(['customer:write'])]
        #[Assert\Length(max: 255)]
        private(set) ?string $streetLine2 = null,

        #[Groups(['customer:write'])]
        #[Assert\Length(max: 150)]
        private(set) ?string $region = null,
    ) {
    }
}
