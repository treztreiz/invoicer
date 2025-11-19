<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Installment;

use App\Application\Service\Transformer\OutputTransformer;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: InstallmentPlan::class)]
final readonly class InstallmentPlanOutput
{
    /**
     * @param list<InstallmentOutput> $installments
     */
    public function __construct(
        #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
        private(set) string $installmentPlanId,

        #[Map(transform: InstallmentOutputTransformer::class)]
        private(set) array $installments,
    ) {
    }
}
