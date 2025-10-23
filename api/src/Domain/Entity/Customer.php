<?php

namespace App\Domain\Entity;

use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Customer
{
    use UuidTrait;
    use TimestampableTrait;

    public function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        private Name $name,

        #[ORM\Embedded(columnPrefix: false)]
        private Contact $contact,

        #[ORM\Embedded]
        private Address $address,

        #[ORM\Column]
        #[Assert\Type(type: ['bool'])]
        private bool $archived = false,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): static
    {
        $this->contact = $contact;

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
