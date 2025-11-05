<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Contact
{
    public function __construct(
        #[ORM\Column(length: 180, nullable: true)]
        private(set) ?string $email = null {
            set => DomainGuard::email($value);
        },

        #[ORM\Column(length: 32, nullable: true)]
        private(set) ?string $phone = null {
            set => DomainGuard::phone($value);
        },
    ) {
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }
}
