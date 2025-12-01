<?php

declare(strict_types=1);

namespace App\Application\Message;

final readonly class RunRecurringInvoicesMessage
{
    public function __construct(
        public \DateTimeImmutable $runDate = new \DateTimeImmutable('today'),
    ) {
    }
}
