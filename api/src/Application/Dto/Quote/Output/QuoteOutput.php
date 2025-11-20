<?php

declare(strict_types=1);

namespace App\Application\Dto\Quote\Output;

use App\Application\Dto\Document\Output\AmountBreakdownOutput;
use App\Application\Dto\Document\Output\AmountBreakdownOutputTransformer;
use App\Application\Dto\Document\Output\DocumentLineOutput;
use App\Application\Dto\Document\Output\DocumentLineOutputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\Document;
use App\Domain\Entity\Document\Quote\Quote;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * @phpstan-import-type CustomerSnapshot from Document
 * @phpstan-import-type CompanySnapshot from Document
 */
#[Map(source: Quote::class)]
final readonly class QuoteOutput
{
    /**
     * @param list<DocumentLineOutput> $lines
     * @param CustomerSnapshot         $customerSnapshot
     * @param CompanySnapshot          $companySnapshot
     * @param list<string>             $availableTransitions
     */
    public function __construct(
        #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $quoteId,

        private(set) string $title,

        private(set) ?string $subtitle,

        #[Map(transform: [OutputTransformer::class, 'backedEnum'])]
        private(set) string $status,

        private(set) string $currency,

        #[Map(transform: [OutputTransformer::class, 'valueObject'])]
        private(set) string $vatRate,

        #[Map(transform: AmountBreakdownOutputTransformer::class)]
        private(set) AmountBreakdownOutput $total,

        #[Map(transform: DocumentLineOutputTransformer::class)]
        private(set) array $lines,

        #[Map(source: 'customer.id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $customerId,

        private(set) array $customerSnapshot,

        private(set) array $companySnapshot,

        #[Map(transform: [OutputTransformer::class, 'dateTime'])]
        private(set) string $createdAt,

        #[Map(source: 'status', transform: QuoteOutputTransitionsTransformer::class)]
        private(set) array $availableTransitions = [],
    ) {
    }
}
