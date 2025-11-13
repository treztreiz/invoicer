<?php

declare(strict_types=1);

namespace App\Application\UseCase\Customer\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class CustomerOutput
{
    public function __construct(
        #[Groups(['customer:read'])]
        private(set) string $customerId,
        #[Groups(['customer:read'])]
        private(set) string $firstName,
        #[Groups(['customer:read'])]
        private(set) string $lastName,
        #[Groups(['customer:read'])]
        private(set) string $email,
        #[Groups(['customer:read'])]
        private(set) ?string $phone,
        #[Groups(['customer:read'])]
        private(set) bool $isArchived,
        #[Groups(['customer:read'])]
        private(set) CustomerAddressOutput $address,
    ) {
    }
}
