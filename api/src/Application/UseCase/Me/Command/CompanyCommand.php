<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Command;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyCommand
{
    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $legalName = null;

    #[Groups(['me:write'])]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Groups(['me:write'])]
    #[Assert\Length(max: 32)]
    public ?string $phone = null;

    #[Groups(['me:write'])]
    #[Assert\Valid]
    public CompanyAddressCommand $address;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Currency]
    public ?string $defaultCurrency = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    public ?string $defaultHourlyRate = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
    public ?string $defaultDailyRate = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/')]
    public ?string $defaultVatRate = null;

    #[Groups(['me:write'])]
    public ?string $legalMention = null;

    public function __construct()
    {
        $this->address = new CompanyAddressCommand();
    }
}
