<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CompanyInput
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
        private(set) string $legalName,

        #[Groups(['user:write'])]
        #[Assert\Valid]
        private(set) CompanyAddressInput $address,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Currency]
        private(set) string $defaultCurrency,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        #[ApiProperty(openapiContext: ['example' => '50.00'])]
        private(set) string $defaultHourlyRate,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        #[ApiProperty(openapiContext: ['example' => '350.00'])]
        private(set) string $defaultDailyRate,

        #[Groups(['user:write'])]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/')]
        #[ApiProperty(openapiContext: ['example' => '00.00'])]
        private(set) string $defaultVatRate,

        #[Groups(['user:write'])]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        private(set) ?string $email = null,

        #[Groups(['user:write'])]
        #[Assert\Length(max: 32)]
        private(set) ?string $phone = null,

        #[Groups(['user:write'])]
        private(set) ?string $legalMention = null,
    ) {
    }
}
