<?php

declare(strict_types=1);

namespace App\Domain\Entity\Customer;

use App\Domain\Entity\Common\ArchivableTrait;
use App\Domain\Entity\Common\TimestampableTrait;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Customer
{
    use UuidTrait;
    use TimestampableTrait;
    use ArchivableTrait;

    public function __construct(
        #[ORM\Embedded(columnPrefix: false)]
        private(set) Name $name,

        #[ORM\Embedded(columnPrefix: false)]
        private(set) Contact $contact,

        #[ORM\Embedded]
        private(set) Address $address,
    ) {
    }
}
