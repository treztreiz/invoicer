<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document;

use App\Domain\Contracts\Payload\DocumentPayloadInterface;
use App\Domain\ValueObject\VatRate;

final class InvoicePayload implements DocumentPayloadInterface
{
    public function __construct(
        protected(set) string $title,
        protected(set) ?string $subtitle,
        protected(set) string $currency,
        protected(set) VatRate $vatRate,
        protected(set) DocumentLinePayloadCollection $linesPayload,
        private(set) readonly ?\DateTimeImmutable $dueDate,
    ) {
    }
}
