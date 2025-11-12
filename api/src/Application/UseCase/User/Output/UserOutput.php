<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class UserOutput
{
    /** @param array<int, string> $roles */
    public function __construct(
        #[Groups(['user:read'])]
        public string $userId,
        #[Groups(['user:read'])]
        public string $firstName,
        #[Groups(['user:read'])]
        public string $lastName,
        #[Groups(['user:read'])]
        public string $email,
        #[Groups(['user:read'])]
        public ?string $phone,
        #[Groups(['user:read'])]
        public string $locale,
        #[Groups(['user:read'])]
        public array $roles,
        #[Groups(['user:read'])]
        public CompanyOutput $company,
    ) {
    }
}
