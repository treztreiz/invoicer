<?php

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class VatRate
{
    public function __construct(
        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        private(set) string $value {
            set => DomainGuard::decimal($value, 2, 'VAT rate', false, 0.0, 100.0);
        }
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
