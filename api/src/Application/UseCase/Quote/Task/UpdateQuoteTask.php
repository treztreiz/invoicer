<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Task;

use App\Application\UseCase\Quote\Input\QuoteInput;

final readonly class UpdateQuoteTask
{
    public function __construct(
        public string $quoteId,
        public QuoteInput $input,
    ) {
    }
}
