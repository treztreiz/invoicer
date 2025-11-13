<?php

declare(strict_types=1);

namespace App\Domain\Filter;

use App\Domain\Enum\QuoteStatus;

final readonly class QuoteFilterCollection
{
    /**
     * @param list<QuoteStatus> $statuses
     */
    public function __construct(
        private(set) ?array $statuses = [],
    ) {
    }
}
