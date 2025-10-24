<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
final readonly class Name
{
    public function __construct(
        #[ORM\Column(length: 150)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        #[Assert\Regex(pattern: '/\@|\d/', message: "Ce prénom n'est pas valide.", match: false)]
        public string $firstName,

        #[ORM\Column(length: 150)]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        #[Assert\Regex(pattern: '/\@|\d/', message: "Ce nom de famille n'est pas valide.", match: false)]
        public string $lastName,
    ) {
    }

    // RICH METHODS ////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->lastName, $this->firstName);
    }
}