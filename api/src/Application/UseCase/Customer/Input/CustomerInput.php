<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomerInput
{
    /**
     * Filled internally for update operations (PUT), not exposed over the wire.
     */
    public string $customerId = '';

    public function __construct(
        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        private(set) readonly string $firstName,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        private(set) readonly string $lastName,

        #[Groups(['customer:write'])]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        private(set) readonly string $email,

        #[Groups(['customer:write'])]
        #[Assert\Valid]
        private(set) readonly CustomerAddressInput $address,

        #[Groups(['customer:write'])]
        #[Assert\Length(max: 32)]
        private(set) readonly ?string $phone = null,
    ) {
    }
}
