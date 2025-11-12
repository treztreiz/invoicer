<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Name
{
    public function __construct(
        #[ORM\Column(length: 150)]
        private(set) string $firstName {
            set => DomainGuard::personName($value, 'First name');
        },

        #[ORM\Column(length: 150)]
        private(set) string $lastName {
            set => DomainGuard::personName($value, 'Last name');
        },
    ) {
    }
}
