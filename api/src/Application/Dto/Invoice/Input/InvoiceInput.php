<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Input;

use App\Application\Dto\Document\Input\DocumentCustomerInputTransformer;
use App\Application\Dto\Document\Input\DocumentLineInput;
use App\Application\Dto\Document\Input\DocumentLineInputTransformer;
use App\Application\Service\Transformer\InputTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final class InvoiceInput
{
    /** @param list<DocumentLineInput> $lines */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        private(set) readonly string $title,

        #[Assert\Length(max: 200)]
        private(set) ?string $subtitle {
            get => $this->subtitle ?? null;
            set => $value;
        },

        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        #[Map(target: 'customer', transform: DocumentCustomerInputTransformer::class)]
        private(set) readonly string $customerId,

        #[Assert\Currency]
        private(set) readonly string $currency,

        #[Assert\PositiveOrZero]
        #[Map(transform: [InputTransformer::class, 'vatRate'])]
        private(set) readonly float $vatRate,

        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        #[Map(target: 'linesPayload', transform: DocumentLineInputTransformer::class)]
        private(set) readonly array $lines,

        #[Assert\NotBlank]
        #[Map(transform: [InputTransformer::class, 'date'])]
        private(set) readonly string $dueDate,
    ) {
    }
}
