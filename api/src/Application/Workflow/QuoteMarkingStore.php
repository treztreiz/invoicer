<?php

declare(strict_types=1);

namespace App\Application\Workflow;

use App\Application\Guard\TypeGuard;
use App\Domain\Entity\Document\Quote;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

final class QuoteMarkingStore implements MarkingStoreInterface
{
    /**
     * @param Quote $subject
     */
    public function getMarking(object $subject): Marking
    {
        TypeGuard::assertClass(Quote::class, $subject);

        return new Marking([$subject->status()->value => 1]);
    }

    /**
     * @param Quote            $subject
     * @param array<int,mixed> $context
     */
    public function setMarking(object $subject, Marking $marking, array $context = []): void
    {
        // no-op: aggregate methods perform transitions
    }
}
