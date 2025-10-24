<?php

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Company
{
    public function __construct(
        #[ORM\Column(length: 255)]
        private string $legalName,

        #[ORM\Embedded(columnPrefix: false)]
        private Contact $contact,

        #[ORM\Embedded]
        private Address $address,

        #[ORM\Column(length: 3)]
        private string $defaultCurrency,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        private string $defaultHourlyRate,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        private string $defaultDailyRate,

        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        private string $defaultVatRate,

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        private ?string $legalMention = null,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function getContact(): Contact
    {
        return $this->contact;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function getDefaultHourlyRate(): string
    {
        return $this->defaultHourlyRate;
    }

    public function getDefaultDailyRate(): string
    {
        return $this->defaultDailyRate;
    }

    public function getDefaultVatRate(): string
    {
        return $this->defaultVatRate;
    }

    public function getLegalMention(): ?string
    {
        return $this->legalMention;
    }
}
