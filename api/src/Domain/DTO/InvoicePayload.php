<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\VatRate;

final readonly class InvoicePayload
{
    /**
     * @param list<DocumentLinePayload> $lines
     * @param array<string, mixed>      $customerSnapshot
     * @param array<string, mixed>      $companySnapshot
     */
    public function __construct(
        public string $title,
        public ?string $subtitle,
        public string $currency,
        public VatRate $vatRate,
        public AmountBreakdown $total,
        public array $lines,
        public array $customerSnapshot,
        public array $companySnapshot,
        public \DateTimeImmutable $dueDate,
    ) {
    }
}
