<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Input;

use App\Application\Dto\Address\Input\AddressInput;
use App\Application\Dto\Address\Input\AddressInputTransformer;
use App\Application\Service\Transformer\InputTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyInput
{
    /**
     * @param numeric-string $defaultHourlyRate
     * @param numeric-string $defaultDailyRate
     * @param numeric-string $defaultVatRate
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        private(set) readonly string $legalName,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        #[Map(target: 'contact', transform: [InputTransformer::class, 'contact'])]
        private(set) readonly string $email,

        #[Assert\Length(max: 32)]
        #[Map(if: false)]
        private(set) ?string $phone {
            get => $this->phone ?? null;
            set => $value;
        },

        #[Assert\Valid]
        #[Map(transform: AddressInputTransformer::class)]
        private(set) readonly AddressInput $address,

        #[Assert\NotBlank]
        #[Assert\Currency]
        private(set) readonly string $defaultCurrency,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        #[Map(transform: [InputTransformer::class, 'money'])]
        private(set) readonly string $defaultHourlyRate,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        #[Map(transform: [InputTransformer::class, 'money'])]
        private(set) readonly string $defaultDailyRate,

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/')]
        #[Map(transform: [InputTransformer::class, 'vatRate'])]
        private(set) readonly string $defaultVatRate,

        private(set) ?string $legalMention {
            get => $this->legalMention ?? null;
            set => $value;
        },
    ) {
    }
}
