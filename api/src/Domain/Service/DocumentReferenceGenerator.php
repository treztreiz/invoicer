<?php

namespace App\Domain\Service;

use App\Domain\Contracts\NumberSequenceRepositoryInterface;
use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use App\Domain\Guard\DomainGuard;
use DateTimeImmutable;
use InvalidArgumentException;

final class DocumentReferenceGenerator
{
    private const array PREFIX_MAP = [
        DocumentType::INVOICE->value => 'INV',
        DocumentType::QUOTE->value => 'Q',
    ];

    public function __construct(
        private readonly NumberSequenceRepositoryInterface $sequenceRepository
    ) {
    }

    public function generate(DocumentType $type, ?int $year = null, int $padding = 4): string
    {
        $prefix = $this->prefixFor($type);

        $year = $year ?? (int)new DateTimeImmutable()->format('Y');
        $year = DomainGuard::nonNegativeInt($year, 'Year');

        if ($year < 1000 || $year > 9999) {
            throw new InvalidArgumentException('Year must be a four-digit value.');
        }

        $sequence = $this->sequenceRepository->findOneByTypeAndYear($type, $year);

        if ($sequence === null) {
            $sequence = new NumberSequence($type, $year);
        }

        $next = $sequence->reserveNext();
        $this->sequenceRepository->save($sequence);

        return sprintf(
            '%s-%d-%s',
            $prefix,
            $year,
            str_pad((string)$next, $padding, '0', STR_PAD_LEFT)
        );
    }

    private function prefixFor(DocumentType $type): string
    {
        return self::PREFIX_MAP[$type->value]
            ?? throw new InvalidArgumentException(sprintf('No prefix defined for document type %s.', $type->value));
    }
}
