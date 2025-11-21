<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\Repository\NumberSequenceRepositoryInterface;
use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use App\Domain\Exception\DomainGuardException;
use App\Domain\Guard\DomainGuard;

final readonly class DocumentReferenceGenerator
{
    public function __construct(private NumberSequenceRepositoryInterface $sequenceRepository)
    {
    }

    public function generate(DocumentType $type, ?int $year = null, int $padding = 4): string
    {
        $year = DomainGuard::nonNegativeInt(
            $year ?: (int) new \DateTimeImmutable()->format('Y'),
            'Year'
        );

        if ($year < 1000 || $year > 9999) {
            throw new DomainGuardException('Year must be a four-digit value.');
        }

        $sequence = $this->sequenceRepository->findOneByTypeAndYear($type, $year) ?: new NumberSequence($type, $year);
        $nextNumber = $sequence->reserveNext(); // Increment next value and retrieve current value

        $this->sequenceRepository->save($sequence);

        $formattedNumber = str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);

        return sprintf('%s-%d-%s', $type->getPrefix(), $year, $formattedNumber);
    }
}
