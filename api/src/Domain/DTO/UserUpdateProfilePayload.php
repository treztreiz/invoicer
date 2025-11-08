<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final class UserUpdateProfilePayload
{
    public function __construct(
        public Name $name,
        public Contact $contact,
        public Company $company,
        public string $locale,
        public string $userIdentifier,
    ) {
    }
}
