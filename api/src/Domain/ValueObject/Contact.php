<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class Contact
{
    public function __construct(
        #[ORM\Column(length: 180, nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        private ?string $email = null,

        #[ORM\Column(length: 32, nullable: true)]
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Regex('/^(0|(\+[0-9]{2}[. -]?))[1-9]([. -]?[0-9][0-9]){4}$/', message: "Ce numéro n'est pas valide.")]
        private ?string $phone = null,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}