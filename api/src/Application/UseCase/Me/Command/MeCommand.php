<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Command;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class MeCommand
{
    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $firstName = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $lastName = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Groups(['me:write'])]
    #[Assert\Length(max: 32)]
    public ?string $phone = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Locale]
    public ?string $locale = null;

    #[Groups(['me:write'])]
    #[Assert\Valid]
    public CompanyCommand $company;

    /**
     * Filled internally to identify the authenticated user.
     * Not exposed via serialization groups.
     */
    public string $userId = '';

    public function __construct()
    {
        $this->company = new CompanyCommand();
    }
}
