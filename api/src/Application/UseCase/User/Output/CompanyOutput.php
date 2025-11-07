<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class CompanyOutput
{
    public function __construct(
        #[Groups(['user:read'])]
        public string $legalName,
        #[Groups(['user:read'])]
        public ?string $email,
        #[Groups(['user:read'])]
        public ?string $phone,
        #[Groups(['user:read'])]
        public CompanyAddressOutput $address,
        #[Groups(['user:read'])]
        public string $defaultCurrency,
        #[Groups(['user:read'])]
        public string $defaultHourlyRate,
        #[Groups(['user:read'])]
        public string $defaultDailyRate,
        #[Groups(['user:read'])]
        public string $defaultVatRate,
        #[Groups(['user:read'])]
        public ?string $legalMention,
        #[Groups(['user:read'])]
        public ?string $logoUrl,
    ) {
    }
}
