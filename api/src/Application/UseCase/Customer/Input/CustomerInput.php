<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomerInput
{
    public function __construct(
        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $firstName,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $lastName,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,

        #[Groups(['customer:write'])]
        #[Assert\Valid]
        public CustomerAddressInput $address,

        #[Groups(['customer:write'])]
        #[Assert\Length(max: 32)]
        public ?string $phone = null,
    ) {
    }
}
