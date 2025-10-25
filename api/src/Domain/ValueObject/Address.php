<?php

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Address
{
    public function __construct(
        #[ORM\Column]
        private(set) string $streetLine1 {
            set => DomainGuard::nonEmpty($value, 'Street line 1');
        },

        #[ORM\Column(nullable: true)]
        private(set) ?string $streetLine2 {
            set => DomainGuard::optionalNonEmpty($value, 'Street line 2');
        },

        #[ORM\Column(length: 20)]
        private(set) string $postalCode {
            set => DomainGuard::nonEmpty($value, 'Postal code');
        },

        #[ORM\Column(length: 150)]
        private(set) string $city {
            set => DomainGuard::nonEmpty($value, 'City');
        },

        #[ORM\Column(length: 150, nullable: true)]
        private(set) ?string $region {
            set => DomainGuard::optionalNonEmpty($value, 'Region');
        },

        #[ORM\Column(length: 2)]
        private(set) string $countryCode {
            set => DomainGuard::countryCode($value);
        },
    ) {
    }
}
