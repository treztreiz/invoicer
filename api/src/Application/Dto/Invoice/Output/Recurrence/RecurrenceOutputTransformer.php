<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Recurrence;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\Document\Invoice\Recurrence;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<Recurrence, RecurrenceOutput> */
final class RecurrenceOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param ?Recurrence $value
     */
    public function __invoke(mixed $value, object $source, ?object $target): ?RecurrenceOutput
    {
        if (null === $value) {
            return null;
        }

        $recurrence = TypeGuard::assertClass(Recurrence::class, $value);

        return $this->objectMapper->map($recurrence, RecurrenceOutput::class);
    }
}
