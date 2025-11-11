<?php

declare(strict_types=1);

namespace App\Domain\DTO;

/**
 * @param list<InstallmentPayload> $installments
 */
final readonly class InstallmentPlanPayload
{
    public function __construct(public array $installments)
    {
    }
}
