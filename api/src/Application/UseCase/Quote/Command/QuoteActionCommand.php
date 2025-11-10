<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Command;

final readonly class QuoteActionCommand
{
    public function __construct(
        public string $quoteId,
        public string $action,
    ) {
    }
}
