<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Command;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyAddressCommand
{
    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $streetLine1 = null;

    #[Groups(['me:write'])]
    #[Assert\Length(max: 255)]
    public ?string $streetLine2 = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public ?string $postalCode = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public ?string $city = null;

    #[Groups(['me:write'])]
    #[Assert\Length(max: 150)]
    public ?string $region = null;

    #[Groups(['me:write'])]
    #[Assert\NotBlank]
    #[Assert\Country]
    public ?string $countryCode = null;
}
