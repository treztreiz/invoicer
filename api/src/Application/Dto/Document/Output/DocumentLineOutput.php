<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Output;

use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\DocumentLine;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: DocumentLine::class)]
final readonly class DocumentLineOutput
{
    public function __construct(
        private(set) string $description,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $quantity,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $rateUnit,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $rate,

        #[Map(transform: AmountBreakdownOutputTransformer::class)]
        private(set) AmountBreakdownOutput $amount,
    ) {
    }
}
