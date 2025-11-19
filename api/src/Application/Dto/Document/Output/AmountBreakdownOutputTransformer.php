<?php

declare(strict_types=1);

namespace App\Application\Dto\Document\Output;

use App\Application\Guard\TypeGuard;
use App\Domain\ValueObject\AmountBreakdown;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<object, object> */
final class AmountBreakdownOutputTransformer implements TransformCallableInterface
{
    /**
     * @param AmountBreakdown $value
     */
    public function __invoke(mixed $value, object $source, ?object $target): AmountBreakdownOutput
    {
        $amount = TypeGuard::assertClass(AmountBreakdown::class, $value);

        return new AmountBreakdownOutput(
            net: $amount->net->value,
            tax: $amount->tax->value,
            gross: $amount->gross->value,
        );
    }
}
