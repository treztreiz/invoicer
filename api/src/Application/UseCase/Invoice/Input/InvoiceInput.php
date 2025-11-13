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
        private(set) readonly string $title,

        #[Groups(['invoice:write'])]
        #[Assert\Currency]
        private(set) readonly string $currency,

        #[Groups(['invoice:write'])]
        #[Assert\PositiveOrZero]
        private(set) readonly float $vatRate,

        /** @var list<DocumentLineInput|array<string, mixed>> */
        #[Groups(['invoice:write'])]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        private(set) readonly array $lines,

        #[Groups(['invoice:write'])]
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        private(set) readonly string $customerId,

        #[Groups(['invoice:write'])]
        #[Assert\NotBlank]
        private(set) readonly string $dueDate,

        #[Groups(['invoice:write'])]
        #[Assert\Length(max: 200)]
        private(set) readonly ?string $subtitle = null,
    ) {
    }
}
