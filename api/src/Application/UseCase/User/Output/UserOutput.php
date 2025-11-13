<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class UserOutput
{
    /** @param array<int, string> $roles */
    public function __construct(
        #[Groups(['user:read'])]
        private(set) string $userId,
        #[Groups(['user:read'])]
        private(set) string $firstName,
        #[Groups(['user:read'])]
        private(set) string $lastName,
        #[Groups(['user:read'])]
        private(set) string $email,
        #[Groups(['user:read'])]
        private(set) ?string $phone,
        #[Groups(['user:read'])]
        private(set) string $locale,
        #[Groups(['user:read'])]
        private(set) array $roles,
        #[Groups(['user:read'])]
        private(set) CompanyOutput $company,
    ) {
    }
}
