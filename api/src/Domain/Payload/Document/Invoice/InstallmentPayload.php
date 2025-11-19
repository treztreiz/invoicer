<?php

declare(strict_types=1);

namespace App\Domain\Payload\Document\Invoice;

use Symfony\Component\Uid\Uuid;

final readonly class InstallmentPayload
{
    public function __construct(
        private(set) ?Uuid $id,
        private(set) string $percentage,
        private(set) ?\DateTimeImmutable $dueDate,
    ) {
    }
}
