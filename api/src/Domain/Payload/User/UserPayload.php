<?php

declare(strict_types=1);

namespace App\Domain\Payload\User;

use App\Domain\ValueObject\Company;
use App\Domain\ValueObject\Contact;
use App\Domain\ValueObject\Name;

final readonly class UserPayload
{
    public function __construct(
        private(set) Name $name,
        private(set) Contact $contact,
        private(set) Company $company,
        private(set) string $userIdentifier,
        private(set) string $locale,
    ) {
    }
}
