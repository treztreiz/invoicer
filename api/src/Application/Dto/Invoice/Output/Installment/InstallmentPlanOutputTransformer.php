<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Installment;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<InstallmentPlan, InstallmentPlanOutput> */
final class InstallmentPlanOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param ?InstallmentPlan $value
     */
    public function __invoke(mixed $value, object $source, ?object $target): ?InstallmentPlanOutput
    {
        if (null === $value) {
            return null;
        }

        $installmentPlan = TypeGuard::assertClass(InstallmentPlan::class, $value);

        return $this->objectMapper->map($installmentPlan, InstallmentPlanOutput::class);
    }
}
