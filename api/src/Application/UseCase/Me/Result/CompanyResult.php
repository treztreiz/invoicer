<?php

declare(strict_types=1);

namespace App\Application\UseCase\Me\Result;

use Symfony\Component\Serializer\Annotation\Groups;

final class CompanyResult
{
    public function __construct(
        #[Groups(['me:read'])]
        public string $legalName,
        #[Groups(['me:read'])]
        public ?string $email,
        #[Groups(['me:read'])]
        public ?string $phone,
        #[Groups(['me:read'])]
        public CompanyAddressResult $address,
        #[Groups(['me:read'])]
        public string $defaultCurrency,
        #[Groups(['me:read'])]
        public string $defaultHourlyRate,
        #[Groups(['me:read'])]
        public string $defaultDailyRate,
        #[Groups(['me:read'])]
        public string $defaultVatRate,
        #[Groups(['me:read'])]
        public ?string $legalMention,
        #[Groups(['me:read'])]
        public ?string $logoUrl,
    ) {
    }
}
