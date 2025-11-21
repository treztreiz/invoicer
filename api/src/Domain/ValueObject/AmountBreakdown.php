<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\DomainGuardException;
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
            throw new DomainGuardException('Gross amount must equal net plus tax.');
        }
    }

    /**
     * @param numeric-string $net
     * @param numeric-string $tax
     * @param numeric-string $gross
     */
    public static function fromValues(string $net, string $tax, string $gross): self
    {
        return new self(
            net: new Money($net),
            tax: new Money($tax),
            gross: new Money($gross),
        );
    }

    public function equals(self $amount): bool
    {
        return $amount->net->value === $this->net->value
            && $amount->tax->value === $this->tax->value
            && $amount->gross->value === $this->gross->value;
    }
}
