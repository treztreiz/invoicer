<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Payload;

use App\Domain\Payload\Document\DocumentLinePayload;
use App\Domain\ValueObject\VatRate;

interface DocumentPayloadInterface
{
    protected(set) string $title {
        get;
        set;
    }

    protected(set) ?string $subtitle {
        get;
        set;
    }

    protected(set) string $currency {
        get;
        set;
    }

    protected(set) VatRate $vatRate {
        get;
        set;
    }

    /** @var list<DocumentLinePayload> $linesPayload */
    protected(set) array $linesPayload {
        get;
        set;
    }
}
