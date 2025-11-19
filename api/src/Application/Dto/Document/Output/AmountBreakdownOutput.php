<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Output;

final readonly class AmountBreakdownOutput
{
    public function __construct(
        private(set) string $net,
        private(set) string $tax,
        private(set) string $gross,
    ) {
    }
}
