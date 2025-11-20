<?php

declare(strict_types=1);

namespace App\Domain\Payload\Customer;

use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final readonly class CustomerPayload
{
    public function __construct(
        private(set) Name $name,
        private(set) ?string $legalName,
        private(set) Contact $contact,
        private(set) Address $address,
    ) {
    }
}
