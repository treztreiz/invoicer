<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\VatRate;

abstract class DocumentPayload
{
    protected(set) string $title;

    protected(set) ?string $subtitle;

    protected(set) string $currency;

    protected(set) VatRate $vatRate;

    protected(set) AmountBreakdown $total;

    /**
     * @var list<DocumentLinePayload>
     */
    protected(set) array $lines;

    /**
     * @var array<string, mixed>
     */
    protected(set) array $customerSnapshot;

    /**
     * @var array<string, mixed>
     */
    protected(set) array $companySnapshot;
}
