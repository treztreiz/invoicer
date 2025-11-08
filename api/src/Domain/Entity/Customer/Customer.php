<?php

declare(strict_types=1);

namespace App\Domain\Entity\Customer;

use App\Domain\DTO\CustomerPayload;
use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
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

    public function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        public Name $name,

        #[ORM\Embedded(columnPrefix: false)]
        public Contact $contact,

        #[ORM\Embedded]
        public Address $address,
    ) {
    }

    public static function fromPayload(CustomerPayload $payload): self
    {
        return new self($payload->name, $payload->contact, $payload->address);
    }

    public function apply(CustomerPayload $payload): void
    {
        $this->name = $payload->name;
        $this->contact = $payload->contact;
        $this->address = $payload->address;
    }
}
