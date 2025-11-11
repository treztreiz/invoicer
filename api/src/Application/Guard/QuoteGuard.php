<?php

declare(strict_types=1);

namespace App\Application\Guard;

use App\Application\Exception\DomainRuleViolationException;
use App\Application\Exception\ResourceNotFoundException;
use App\Domain\Entity\Document\Quote;
use App\Domain\Enum\QuoteStatus;

final class QuoteGuard
{
    public static function assertFound(?Quote $quote, string $id): Quote
    {
        if (!$quote instanceof Quote) {
            throw new ResourceNotFoundException('Quote', $id);
        }

        return $quote;
    }

    public static function assertDraft(Quote $quote): Quote
    {
        if (QuoteStatus::DRAFT !== $quote->status) {
            throw new DomainRuleViolationException('Only draft quotes can be updated.');
        }

        return $quote;
    }
}
