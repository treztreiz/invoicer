<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Installment;

use App\Application\Dto\Document\Output\AmountBreakdownOutput;
use App\Application\Dto\Document\Output\AmountBreakdownOutputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\Invoice\Installment;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: Installment::class)]
final readonly class InstallmentOutput
{
    public function __construct(
        #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $installmentId,

        private(set) int $position,

        private(set) string $percentage,

        #[Map(transform: AmountBreakdownOutputTransformer::class)]
        private(set) AmountBreakdownOutput $amount,

        #[Map(transform: [OutputTransformer::class, 'date'])]
        private(set) ?string $dueDate,
    ) {
    }
}
