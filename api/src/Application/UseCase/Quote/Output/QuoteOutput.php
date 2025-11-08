<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class QuoteOutput
{
    /**
     * @param list<QuoteLineOutput> $lines
     * @param array<string, mixed>  $customerSnapshot
     * @param array<string, mixed>  $companySnapshot
     */
    public function __construct(
        #[Groups(['quote:read'])]
        public string $id,

        #[Groups(['quote:read'])]
        public string $title,

        #[Groups(['quote:read'])]
        public ?string $subtitle,

        #[Groups(['quote:read'])]
        public string $status,

        #[Groups(['quote:read'])]
        public string $currency,

        #[Groups(['quote:read'])]
        public string $vatRate,

        #[Groups(['quote:read'])]
        public QuoteTotalsOutput $total,

        #[Groups(['quote:read'])]
        public array $lines,

        #[Groups(['quote:read'])]
        public array $customerSnapshot,

        #[Groups(['quote:read'])]
        public array $companySnapshot,

        #[Groups(['quote:read'])]
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
