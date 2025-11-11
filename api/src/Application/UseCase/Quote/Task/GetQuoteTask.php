<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Task;

final readonly class GetQuoteTask
{
    public function __construct(public string $quoteId)
    {
    }
}
