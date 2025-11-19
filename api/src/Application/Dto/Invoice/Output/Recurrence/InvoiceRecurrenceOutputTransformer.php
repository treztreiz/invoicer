<?php

declare(strict_types=1);

namespace App\Application\Dto\Invoice\Output\Recurrence;

use App\Application\Guard\TypeGuard;
use App\Application\Service\Trait\ObjectMapperAwareTrait;
use App\Domain\Entity\Document\Invoice\InvoiceRecurrence;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/** @implements TransformCallableInterface<InvoiceRecurrence, InvoiceRecurrenceOutput> */
final class InvoiceRecurrenceOutputTransformer implements TransformCallableInterface
{
    use ObjectMapperAwareTrait;

    /**
     * @param ?InvoiceRecurrence $value
     */
    public function __invoke(mixed $value, object $source, ?object $target): ?InvoiceRecurrenceOutput
    {
        if (null === $value) {
            return null;
        }

        $recurrence = TypeGuard::assertClass(InvoiceRecurrence::class, $value);

        return $this->objectMapper->map($recurrence, InvoiceRecurrenceOutput::class);
    }
}
