<?php

namespace App\Domain\Entity;

use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\ValueObject\Address;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Customer
{
    use UuidTrait;
    use TimestampableTrait;

    public function __construct(
        #[ORM\Column(length: 150)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        #[Assert\Regex(pattern: '/\@|\d/', message: "Ce prénom n'est pas valide.", match: false)]
        private string $firstName,

        #[ORM\Column(length: 150)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        #[Assert\Regex(pattern: '/\@|\d/', message: "Ce nom de famille n'est pas valide.", match: false)]
        private string $lastName,

        #[ORM\Column(length: 180, nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        private ?string $email = null,

        #[ORM\Column(length: 32, nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Regex('/^(0|(\+[0-9]{2}[. -]?))[1-9]([. -]?[0-9][0-9]){4}$/', message: "Ce numéro n'est pas valide.")]
        private ?string $phone = null,

        #[ORM\Embedded(Address::class)]
        #[Assert\Valid]
        private Address $address,

        #[ORM\Column]
        #[Assert\Type(type: ['bool'])]
        private bool $archived = false,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): static
    {
        $this->archived = $archived;

        return $this;
    }
}
