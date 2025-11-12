<?php

declare(strict_types=1);

namespace App\Application\Guard;

use App\Application\Exception\DomainRuleViolationException;
use App\Domain\Entity\Document\Quote;
use App\Domain\Enum\QuoteStatus;

final class QuoteGuard
{
    private function __construct()
    {
    }

    public static function assertDraft(Quote $quote): Quote
    {
        if (QuoteStatus::DRAFT !== $quote->status) {
            throw new DomainRuleViolationException('Only draft quotes can be updated.');
        }

        return $quote;
    }
}
