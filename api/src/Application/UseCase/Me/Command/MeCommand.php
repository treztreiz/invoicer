<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Command;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class MeCommand
{
    /**
     * Filled internally to identify the authenticated user.
     * Not exposed via serialization groups.
     */
    public string $userId = '';

    public function __construct(
        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $firstName,

        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $lastName,

        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,

        #[Groups(['me:write'])]
        #[Assert\NotBlank]
        #[Assert\Locale]
        public string $locale,

        #[Groups(['me:write'])]
        #[Assert\Valid]
        public CompanyCommand $company,

        #[Groups(['me:write'])]
        #[Assert\Length(max: 32)]
        public ?string $phone = null,
    ) {
    }
}
