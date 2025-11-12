<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final readonly class CustomerPayload
{
    public function __construct(
        private(set) Name $name,
        private(set) Contact $contact,
        private(set) Address $address,
    ) {
    }
}
