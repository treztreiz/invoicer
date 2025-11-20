<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Installment;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\Document\Invoice\Installment;
use App\Domain\Entity\Document\Invoice\InstallmentPlan;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<InstallmentPlan, InstallmentPlanOutput> */
final class InstallmentOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param list<InstallmentPlan> $value
     *
     * @return list<InstallmentOutput>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return TypeGuard::assertClass(Collection::class, $value)
            ->map(fn (Installment $installment) => $this->transform($installment))
            ->getValues();
    }

    private function transform(Installment $installment): InstallmentOutput
    {
        return $this->objectMapper->map($installment, InstallmentOutput::class);
    }
}
