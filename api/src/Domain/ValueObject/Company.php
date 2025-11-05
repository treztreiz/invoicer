<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Company
{
    public function __construct(
        #[ORM\Column(length: 255)]
        private(set) string $legalName {
            set => DomainGuard::nonEmpty($value, 'Legal name');
        },

        #[ORM\Embedded(columnPrefix: false)]
        private(set) readonly Contact $contact,

        #[ORM\Embedded]
        private(set) readonly Address $address,

        #[ORM\Column(length: 3)]
        private(set) string $defaultCurrency {
            set => DomainGuard::currency($value);
        },

        #[ORM\Embedded]
        private(set) readonly Money $defaultHourlyRate,

        #[ORM\Embedded]
        private(set) readonly Money $defaultDailyRate,

        #[ORM\Embedded]
        private(set) readonly VatRate $defaultVatRate,

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private(set) ?string $legalMention = null {
            set => DomainGuard::optionalNonEmpty($value, 'Legal mention');
        },
    ) {
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function defaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function defaultHourlyRate(): Money
    {
        return $this->defaultHourlyRate;
    }

    public function defaultDailyRate(): Money
    {
        return $this->defaultDailyRate;
    }

    public function defaultVatRate(): VatRate
    {
        return $this->defaultVatRate;
    }

    public function legalMention(): ?string
    {
        return $this->legalMention;
    }
}
