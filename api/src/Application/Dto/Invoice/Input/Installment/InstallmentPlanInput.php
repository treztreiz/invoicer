<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Input\Installment;

use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InstallmentPlanInput
{
    /**
     * @param InstallmentInput[] $installments
     */
    public function __construct(
        #[Assert\Valid]
        #[Assert\Count(min: 1)]
        #[Map(transform: InstallmentInputTransformer::class)]
        private(set) array $installments,
    ) {
    }
}
