<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Output;

use App\Application\UseCase\Document\Output\DocumentLineOutput;
use Symfony\Component\Serializer\Annotation\Groups;

final readonly class QuoteOutput
{
    /**
     * @param list<DocumentLineOutput> $lines
     * @param array<string, mixed>     $customerSnapshot
     * @param array<string, mixed>     $companySnapshot
     * @param list<string>             $availableTransitions
     */
    public function __construct(
        #[Groups(['quote:read'])]
        private(set) string $quoteId,
        #[Groups(['quote:read'])]
        private(set) string $title,
        #[Groups(['quote:read'])]
        private(set) ?string $subtitle,
        #[Groups(['quote:read'])]
        private(set) string $status,
        #[Groups(['quote:read'])]
        private(set) string $currency,
        #[Groups(['quote:read'])]
        private(set) string $vatRate,
        #[Groups(['quote:read'])]
        private(set) QuoteTotalsOutput $total,
        #[Groups(['quote:read'])]
        private(set) array $lines,
        #[Groups(['quote:read'])]
        private(set) array $customerSnapshot,
        #[Groups(['quote:read'])]
        private(set) array $companySnapshot,
        #[Groups(['quote:read'])]
        private(set) \DateTimeImmutable $createdAt,
        #[Groups(['quote:read'])]
        private(set) array $availableTransitions = [],
    ) {
    }
}
