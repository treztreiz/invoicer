<?php

declare(strict_types=1);

namespace App\Application\UseCase\Quote\Input;

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
        public string $title,

        #[Groups(['quote:write'])]
        #[Assert\Currency]
        public string $currency,

        #[Groups(['quote:write'])]
        #[Assert\PositiveOrZero]
        public float $vatRate,

        /**
         * @var list<QuoteLineInput|array<string, mixed>>
         */
        #[Groups(['quote:write'])]
        #[Assert\Count(min: 1)]
        #[Assert\Valid]
        public array $lines,

        #[Groups(['quote:write'])]
        #[Assert\NotBlank]
        public string $customerId,

        #[Groups(['quote:write'])]
        #[Assert\Length(max: 200)]
        public ?string $subtitle = null,
    ) {
    }
}
