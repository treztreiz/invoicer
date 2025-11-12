<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Money
{
    public function __construct(
        /** @var numeric-string */
        #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
        private(set) string $value {
            set => DomainGuard::decimal($value, 2, 'Money amount');
        },
    ) {
    }
}
