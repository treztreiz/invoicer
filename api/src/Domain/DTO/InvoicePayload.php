<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\VatRate;

final class InvoicePayload extends DocumentPayload
{
    /**
     * @param list<DocumentLinePayload> $lines
     * @param array<string, mixed>      $customerSnapshot
     * @param array<string, mixed>      $companySnapshot
     */
    public function __construct(
        protected(set) string $title,
        protected(set) ?string $subtitle,
        protected(set) string $currency,
        protected(set) VatRate $vatRate,
        protected(set) AmountBreakdown $total,
        protected(set) array $lines,
        protected(set) array $customerSnapshot,
        protected(set) array $companySnapshot,
        private(set) readonly ?\DateTimeImmutable $dueDate,
    ) {
    }
}
