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
        private string $streetLine1,

        #[ORM\Column(nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 255)]
        private ?string $streetLine2,

        #[ORM\Column(length: 20)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        private string $postalCode,

        #[ORM\Column(length: 150)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        private string $city,

        #[ORM\Column(length: 150, nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(max: 150)]
        private ?string $region,

        #[ORM\Column(length: 2)]
        #[Assert\NotBlank]
        #[Assert\Country]
        private string $countryCode,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function empty(): self
    {
        return new self('', null, '', '', null, '');
    }

    public function getStreetLine1(): string
    {
        return $this->streetLine1;
    }

    public function getStreetLine2(): ?string
    {
        return $this->streetLine2;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }
}
