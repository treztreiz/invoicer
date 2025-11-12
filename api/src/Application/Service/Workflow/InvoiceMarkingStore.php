<?php

declare(strict_types=1);

namespace App\Application\Service\Workflow;

use App\Application\Guard\TypeGuard;
use App\Domain\Entity\Document\Invoice;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

final class InvoiceMarkingStore implements MarkingStoreInterface
{
    /**
     * @param Invoice&object $subject
     */
    public function getMarking(object $subject): Marking
    {
        TypeGuard::assertClass(Invoice::class, $subject);

        return new Marking([$subject->status->value => 1]);
    }

    /**
     * @param Invoice&object   $subject
     * @param array<int,mixed> $context
     */
    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        // no-op, domain handles transitions
    }
}
