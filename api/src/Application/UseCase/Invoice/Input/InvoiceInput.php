<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input;

use App\Application\UseCase\Document\Input\DocumentLineInput;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceInput
{
    /**
     * Filled internally to identify the authenticated user.
     */
    public string $userId = '';

    public function __construct(
        #[Groups(['invoice:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        public string $title,

        #[Groups(['invoice:write'])]
        #[Assert\Currency]
        public string $currency,

        #[Groups(['invoice:write'])]
        #[Assert\PositiveOrZero]
        public float $vatRate,

        /** @var list<DocumentLineInput|array<string, mixed>> */
        #[Groups(['invoice:write'])]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public array $lines,

        #[Groups(['invoice:write'])]
        #[Assert\NotBlank]
        public string $customerId,

        #[Groups(['invoice:write'])]
        #[Assert\NotBlank]
        public string $dueDate,

        #[Groups(['invoice:write'])]
        #[Assert\Length(max: 200)]
        public ?string $subtitle = null,
    ) {
    }
}
