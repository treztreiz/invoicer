<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class AmountBreakdown
{
    public function __construct(
        #[ORM\Embedded]
        private(set) Money $net,

        #[ORM\Embedded]
        private(set) Money $tax,

        #[ORM\Embedded]
        private(set) Money $gross,
    ) {
        $sum = \bcadd($net->value, $tax->value, 2);

        if ($sum !== $gross->value) {
            throw new \InvalidArgumentException('Gross amount must equal net plus tax.');
        }
    }
}
