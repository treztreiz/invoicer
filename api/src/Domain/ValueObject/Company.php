<?php

namespace App\Domain\ValueObject;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Company
{
    public function __construct(
        #[ORM\Column(length: 255)]
        public string $legalName,

        #[ORM\Embedded(columnPrefix: false)]
        public Contact $contact,

        #[ORM\Embedded]
        public Address $address,

        #[ORM\Column(length: 3)]
        public string $defaultCurrency,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        public string $defaultHourlyRate,

        #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
        public string $defaultDailyRate,

        #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
        public string $defaultVatRate,

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        public ?string $legalMention = null,
    ) {
    }
}
