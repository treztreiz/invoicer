<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final readonly class UserUpdateProfilePayload
{
    public function __construct(
        private(set) Name $name,
        private(set) Contact $contact,
        private(set) Company $company,
        private(set) string $locale,
        private(set) string $userIdentifier,
    ) {
    }
}
