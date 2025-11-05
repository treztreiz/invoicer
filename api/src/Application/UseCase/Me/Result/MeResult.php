<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Result;

use Symfony\Component\Serializer\Annotation\Groups;

final class MeResult
{
    /** @param array<int, string> $roles */
    public function __construct(
        #[Groups(['me:read'])]
        public string $id,
        #[Groups(['me:read'])]
        public string $firstName,
        #[Groups(['me:read'])]
        public string $lastName,
        #[Groups(['me:read'])]
        public string $email,
        #[Groups(['me:read'])]
        public ?string $phone,
        #[Groups(['me:read'])]
        public string $locale,
        #[Groups(['me:read'])]
        public array $roles,
        #[Groups(['me:read'])]
        public CompanyResult $company,
    ) {
    }
}
