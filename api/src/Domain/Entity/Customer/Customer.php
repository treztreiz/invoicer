<?php

declare(strict_types=1);

namespace App\Domain\Entity\Customer;

use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Payload\Customer\CustomerPayload;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer')]
class Customer
{
    use UuidTrait;
    use TimestampableTrait;
    use ArchivableTrait;

    private function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        private(set) Name $name,

        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        private(set) ?string $legalName {
            get => $this->legalName ?? null;
            set => $value;
        },

        #[ORM\Embedded(columnPrefix: false)]
        private(set) Contact $contact,

        #[ORM\Embedded]
        private(set) Address $address,
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(CustomerPayload $payload): self
    {
        return new self($payload->name, $payload->legalName, $payload->contact, $payload->address);
    }

    public function applyPayload(CustomerPayload $payload): void
    {
        $this->name = $payload->name;
        $this->legalName = $payload->legalName;
        $this->contact = $payload->contact;
        $this->address = $payload->address;
    }
}
