<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Input;

use App\Application\Service\Transformer\InputTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class UserInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        #[Map(target: 'name', transform: [InputTransformer::class, 'name'])]
        private(set) readonly string $firstName,

        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        #[Map(if: false)]
        private(set) readonly string $lastName,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        #[Map(target: 'contact', transform: [InputTransformer::class, 'contact'])]
        #[Map(target: 'userIdentifier')]
        private(set) readonly string $email,

        #[Assert\Length(max: 32)]
        #[Map(if: false)]
        private(set) ?string $phone {
            get => $this->phone ?? null;
            set => $value;
        },

        #[Assert\NotBlank]
        #[Assert\Locale]
        private(set) readonly string $locale,

        #[Assert\Valid]
        #[Map(transform: CompanyInputTransformer::class)]
        private(set) readonly CompanyInput $company,
    ) {
    }
}
