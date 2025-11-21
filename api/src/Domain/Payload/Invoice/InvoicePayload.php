<?php

declare(strict_types=1);

namespace App\Domain\Payload\Invoice;

use App\Domain\Contracts\Payload\DocumentPayloadInterface;
use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\ValueObject\VatRate;

final class InvoicePayload implements DocumentPayloadInterface
{
    /** @param list<DocumentLinePayload> $linesPayload */
    public function __construct(
        protected(set) string $title,
        protected(set) ?string $subtitle,
        protected(set) string $currency,
        protected(set) VatRate $vatRate,
        protected(set) array $linesPayload,
        private(set) readonly ?\DateTimeImmutable $dueDate,
    ) {
    }
}
