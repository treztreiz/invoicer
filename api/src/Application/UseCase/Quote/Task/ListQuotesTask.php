<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Task;

use App\Domain\Filter\QuoteFilterCollection;

final readonly class ListQuotesTask
{
    public function __construct(
        private(set) QuoteFilterCollection $filters,
    ) {
    }
}
