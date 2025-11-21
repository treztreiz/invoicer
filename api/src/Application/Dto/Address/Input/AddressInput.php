<?php

declare(strict_types=1);

namespace App\Application\Dto\Address\Input;

use App\Domain\ValueObject\Address;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

#[Map(target: Address::class)]
final class AddressInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private(set) readonly string $streetLine1,

        #[Assert\Length(max: 255)]
        private(set) ?string $streetLine2 {
            get => $this->streetLine2 ?? null;
            set => $value;
        },

        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        private(set) readonly string $postalCode,

        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        private(set) readonly string $city,

        #[Assert\Length(max: 150)]
        private(set) ?string $region {
            get => $this->region ?? null;
            set => $value;
        },

        #[Assert\NotBlank]
        #[Assert\Country]
        private(set) readonly string $countryCode,
    ) {
    }
}
