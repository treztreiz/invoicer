<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output;

use App\Application\Dto\Document\Output\AmountBreakdownOutput;
use App\Application\Dto\Document\Output\AmountBreakdownOutputTransformer;
use App\Application\Dto\Document\Output\DocumentLineOutput;
use App\Application\Dto\Document\Output\DocumentLineOutputTransformer;
use App\Application\Dto\Invoice\Output\Installment\InstallmentPlanOutput;
use App\Application\Dto\Invoice\Output\Installment\InstallmentPlanOutputTransformer;
use App\Application\Dto\Invoice\Output\Recurrence\RecurrenceOutput;
use App\Application\Dto\Invoice\Output\Recurrence\RecurrenceOutputTransformer;
use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\Document;
use App\Domain\Entity\Document\Invoice\Invoice;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * @phpstan-import-type CustomerSnapshot from Document
 * @phpstan-import-type CompanySnapshot from Document
 */
#[Map(source: Invoice::class)]
final readonly class InvoiceOutput
{
    /**
     * @param list<DocumentLineOutput> $lines
     * @param CustomerSnapshot         $customerSnapshot
     * @param CompanySnapshot          $companySnapshot
     * @param list<string>             $availableTransitions
     */
    public function __construct(
        #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $invoiceId,

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
        private(set) ?string $issuedAt,

        #[Map(transform: [OutputTransformer::class, 'date'])]
        private(set) ?string $dueDate,

        #[Map(transform: [OutputTransformer::class, 'dateTime'])]
        private(set) ?string $paidAt,

        #[Map(transform: RecurrenceOutputTransformer::class)]
        private(set) ?RecurrenceOutput $recurrence = null,

        #[Map(transform: InstallmentPlanOutputTransformer::class)]
        private(set) ?InstallmentPlanOutput $installmentPlan = null,

        #[Map(source: 'status', transform: InvoiceOutputTransitionsTransformer::class)]
        private(set) array $availableTransitions = [],
    ) {
    }
}
