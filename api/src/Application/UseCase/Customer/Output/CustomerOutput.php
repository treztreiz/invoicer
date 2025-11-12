<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class CustomerOutput
{
    public function __construct(
        #[Groups(['customer:read'])]
        public string $customerId,

        #[Groups(['customer:read'])]
        public string $firstName,

        #[Groups(['customer:read'])]
        public string $lastName,

        #[Groups(['customer:read'])]
        public string $email,

        #[Groups(['customer:read'])]
        public ?string $phone,

        #[Groups(['customer:read'])]
        public bool $isArchived,

        #[Groups(['customer:read'])]
        public CustomerAddressOutput $address,
    ) {
    }
}
