<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;

final readonly class DocumentLinePayloadCollection
{
    /**
     * @param list<DocumentLinePayload> $lines
     */
    public function __construct(
        public array $lines,
        public AmountBreakdown $total,
    ) {
    }
}
