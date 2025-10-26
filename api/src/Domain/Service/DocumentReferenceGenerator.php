<?php

namespace App\Domain\Service;

use App\Domain\Contracts\NumberSequenceRepositoryInterface;
use App\Domain\Entity\Numbering\NumberSequence;
use App\Domain\Enum\DocumentType;
use App\Domain\Guard\DomainGuard;

final class DocumentReferenceGenerator
{
    private const array PREFIX_MAP = [
        DocumentType::INVOICE->value => 'INV',
        DocumentType::QUOTE->value => 'Q',
    ];

    public function __construct(
        private readonly NumberSequenceRepositoryInterface $sequenceRepository,
    ) {
    }

    public function generate(DocumentType $type, ?int $year = null, int $padding = 4): string
    {
        $year = $year ?? (int) new \DateTimeImmutable()->format('Y');
        $year = DomainGuard::nonNegativeInt($year, 'Year');

        if ($year < 1000 || $year > 9999) {
            throw new \InvalidArgumentException('Year must be a four-digit value.');
        }

        $prefix = $this->prefixFor($type);

        $sequence = $this->sequenceRepository->findOneByTypeAndYear($type, $year);

        if (null === $sequence) {
            $sequence = new NumberSequence($type, $year);
        }

        $next = $sequence->reserveNext();
        $this->sequenceRepository->save($sequence);

        return sprintf(
            '%s-%d-%s',
            $prefix,
            $year,
            str_pad((string) $next, $padding, '0', STR_PAD_LEFT)
        );
    }

    private function prefixFor(DocumentType $type): string
    {
        return match ($type) {
            DocumentType::INVOICE => self::PREFIX_MAP[DocumentType::INVOICE->value],
            DocumentType::QUOTE => self::PREFIX_MAP[DocumentType::QUOTE->value],
        };
    }
}
