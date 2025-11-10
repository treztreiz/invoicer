<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class InvoiceOutput
{
    /**
     * @param list<InvoiceLineOutput> $lines
     * @param array<string, mixed>    $customerSnapshot
     * @param array<string, mixed>    $companySnapshot
     * @param list<string>            $availableActions
     */
    public function __construct(
        #[Groups(['invoice:read'])]
        public string $id,

        #[Groups(['invoice:read'])]
        public string $title,

        #[Groups(['invoice:read'])]
        public ?string $subtitle,

        #[Groups(['invoice:read'])]
        public string $status,

        #[Groups(['invoice:read'])]
        public string $currency,

        #[Groups(['invoice:read'])]
        public string $vatRate,

        #[Groups(['invoice:read'])]
        public InvoiceTotalsOutput $total,

        #[Groups(['invoice:read'])]
        public array $lines,

        #[Groups(['invoice:read'])]
        public array $customerSnapshot,

        #[Groups(['invoice:read'])]
        public array $companySnapshot,

        #[Groups(['invoice:read'])]
        public ?string $issuedAt,

        #[Groups(['invoice:read'])]
        public ?string $dueDate,

        #[Groups(['invoice:read'])]
        public ?string $paidAt,

        #[Groups(['invoice:read'])]
        public ?InvoiceRecurrenceOutput $recurrence = null,

        #[Groups(['invoice:read'])]
        public array $availableActions = [],
    ) {
    }
}
