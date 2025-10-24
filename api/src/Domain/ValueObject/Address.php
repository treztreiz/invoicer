<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
final readonly class Address
{
    public function __construct(
        #[ORM\Column]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $streetLine1,

        #[ORM\Column(nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        public ?string $streetLine2,

        #[ORM\Column(length: 20)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        public string $postalCode,

        #[ORM\Column(length: 150)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $city,

        #[ORM\Column(length: 150, nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 150)]
        public ?string $region,

        #[ORM\Column(length: 2)]
        #[Assert\NotBlank]
        #[Assert\Country]
        public string $countryCode,
    ) {
    }
}
