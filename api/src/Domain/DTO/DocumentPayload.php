<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\VatRate;

abstract class DocumentPayload
{
    abstract protected(set) string $title {
        get;
        set;
    }

    abstract protected(set) ?string $subtitle {
        get;
        set;
    }

    abstract protected(set) string $currency {
        get;
        set;
    }

    abstract protected(set) VatRate $vatRate {
        get;
        set;
    }

    abstract protected(set) AmountBreakdown $total {
        get;
        set;
    }

    /**
     * @var list<DocumentLinePayload> $lines
     */
    abstract protected(set) array $lines {
        get;
        set;
    }

    /**
     * @var array<string, mixed> $customerSnapshot
     */
    abstract protected(set) array $customerSnapshot {
        get;
        set;
    }

    /**
     * @var array<string, mixed> $companySnapshot
     */
    abstract protected(set) array $companySnapshot {
        get;
        set;
    }
}
