<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class CompanyOutput
{
    public function __construct(
        #[Groups(['user:read'])]
        private(set) string $legalName,
        #[Groups(['user:read'])]
        private(set) ?string $email,
        #[Groups(['user:read'])]
        private(set) ?string $phone,
        #[Groups(['user:read'])]
        private(set) CompanyAddressOutput $address,
        #[Groups(['user:read'])]
        private(set) string $defaultCurrency,
        #[Groups(['user:read'])]
        private(set) string $defaultHourlyRate,
        #[Groups(['user:read'])]
        private(set) string $defaultDailyRate,
        #[Groups(['user:read'])]
        private(set) string $defaultVatRate,
        #[Groups(['user:read'])]
        private(set) ?string $legalMention,
        #[Groups(['user:read'])]
        private(set) ?string $logoUrl,
    ) {
    }
}
