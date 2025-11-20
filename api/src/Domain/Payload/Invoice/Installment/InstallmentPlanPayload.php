<?php

declare(strict_types=1);

namespace App\Domain\Payload\Invoice\Installment;

final readonly class InstallmentPlanPayload
{
    /**
     * @param list<InstallmentPayload> $installments
     */
    public function __construct(private(set) array $installments)
    {
    }
}
