<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Input\Installment;

use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Payload\Invoice\Installment\InstallmentPayload;
use App\Domain\Payload\Invoice\Installment\InstallmentPlanPayload;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<InstallmentPlanInput, InstallmentPlanPayload> */
final class InstallmentInputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /** @param list<InstallmentInput> $value
     *
     * @return list<InstallmentPayload>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return array_map(
            fn (InstallmentInput $installmentInput) => $this->transform($installmentInput),
            $value
        );
    }

    private function transform(InstallmentInput $installmentInput): InstallmentPayload
    {
        return $this->objectMapper->map($installmentInput, InstallmentPayload::class);
    }
}
