<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UserInput
{
    /**
     * Filled internally to identify the authenticated user.
     * Not exposed via serialization groups.
     */
    public string $userId = '';

    public function __construct(
        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $firstName,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $lastName,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Locale]
        public string $locale,

        #[Groups(['user:write'])]
        #[Assert\Valid]
        public CompanyInput $company,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 32)]
        public ?string $phone = null,
    ) {
    }
}
