<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document;

use App\Domain\ValueObject\AmountBreakdown;

final readonly class DocumentLinePayloadCollection
{
    /**
     * @param list<DocumentLinePayload> $lines
     */
    public function __construct(
        private(set) array $lines,
        private(set) AmountBreakdown $total,
    ) {
    }
}
