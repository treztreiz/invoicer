<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use App\Application\UseCase\Document\Output\DocumentLineOutput;
use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceOutput
{
    /**
     * @param list<DocumentLineOutput> $lines
     * @param array<string, mixed>     $customerSnapshot
     * @param array<string, mixed>     $companySnapshot
     * @param list<string>             $availableTransitions
     */
    public function __construct(
        #[Groups(['invoice:read'])]
        private(set) string $invoiceId,
        #[Groups(['invoice:read'])]
        private(set) string $title,
        #[Groups(['invoice:read'])]
        private(set) ?string $subtitle,
        #[Groups(['invoice:read'])]
        private(set) string $status,
        #[Groups(['invoice:read'])]
        private(set) string $currency,
        #[Groups(['invoice:read'])]
        private(set) string $vatRate,
        #[Groups(['invoice:read'])]
        private(set) InvoiceTotalsOutput $total,
        #[Groups(['invoice:read'])]
        private(set) array $lines,
        #[Groups(['invoice:read'])]
        private(set) array $customerSnapshot,
        #[Groups(['invoice:read'])]
        private(set) array $companySnapshot,
        #[Groups(['invoice:read'])]
        private(set) ?string $issuedAt,
        #[Groups(['invoice:read'])]
        private(set) ?string $dueDate,
        #[Groups(['invoice:read'])]
        private(set) ?string $paidAt,
        #[Groups(['invoice:read'])]
        private(set) ?InvoiceRecurrenceOutput $recurrence = null,
        #[Groups(['invoice:read'])]
        private(set) ?InvoiceInstallmentPlanOutput $installmentPlan = null,
        #[Groups(['invoice:read'])]
        private(set) array $availableTransitions = [],
    ) {
    }
}
