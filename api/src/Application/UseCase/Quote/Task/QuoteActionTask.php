<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Task;

final readonly class QuoteActionTask
{
    public function __construct(
        public string $quoteId,
        public string $action,
    ) {
    }
}
