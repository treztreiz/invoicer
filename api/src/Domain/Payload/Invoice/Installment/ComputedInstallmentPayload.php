<?php

declare(strict_types=1);

namespace App\Domain\Payload\Invoice\Installment;

use App\Domain\ValueObject\AmountBreakdown;
use Symfony\Component\Uid\Uuid;

final class ComputedInstallmentPayload
{
    public ?Uuid $id {
        get => $this->payload->id;
    }

    public string $percentage {
        get => $this->payload->percentage;
    }

    public ?\DateTimeImmutable $dueDate {
        get => $this->payload->dueDate;
    }

    public function __construct(
        private(set) readonly InstallmentPayload $payload,
        private(set) readonly int $position,
        private(set) readonly AmountBreakdown $amount,
    ) {
    }
}
