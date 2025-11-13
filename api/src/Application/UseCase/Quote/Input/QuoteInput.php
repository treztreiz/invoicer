<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input;

use App\Application\UseCase\Document\Input\DocumentLineInput;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class QuoteInput
{
    /**
     * Filled internally to identify the authenticated user.
     */
    public string $userId = '';

    public function __construct(
        #[Groups(['quote:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        private(set) readonly string $title,

        #[Groups(['quote:write'])]
        #[Assert\Currency]
        private(set) readonly string $currency,

        #[Groups(['quote:write'])]
        #[Assert\PositiveOrZero]
        private(set) readonly float $vatRate,

        /** @var list<DocumentLineInput|array<string, mixed>> */
        #[Groups(['quote:write'])]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        private(set) readonly array $lines,

        #[Groups(['quote:write'])]
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        private(set) readonly string $customerId,

        #[Groups(['quote:write'])]
        #[Assert\Length(max: 200)]
        private(set) readonly ?string $subtitle = null,
    ) {
    }
}
