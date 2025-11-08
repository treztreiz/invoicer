<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyInput
{
    /**
     * @param numeric-string $defaultHourlyRate
     * @param numeric-string $defaultDailyRate
     * @param numeric-string $defaultVatRate
     */
    public function __construct(
        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $legalName,

        #[Groups(['user:write'])]
        #[Assert\Valid]
        public CompanyAddressInput $address,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Currency]
        public string $defaultCurrency,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        #[ApiProperty(openapiContext: ['example' => '50.00'])]
        public string $defaultHourlyRate,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        #[ApiProperty(openapiContext: ['example' => '350.00'])]
        public string $defaultDailyRate,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/')]
        #[ApiProperty(openapiContext: ['example' => '00.00'])]
        public string $defaultVatRate,

        #[Groups(['user:write'])]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public ?string $email = null,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 32)]
        public ?string $phone = null,

        #[Groups(['user:write'])]
        public ?string $legalMention = null,
    ) {
    }
}
