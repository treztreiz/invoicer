<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Quantity
{
    public function __construct(
        #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
        private(set) string $value {
            set => DomainGuard::decimal($value, 3, 'Quantity');
        },
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
