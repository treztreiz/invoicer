<?php

declare(strict_types=1);

namespace App\Domain\Entity\Numbering;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Enum\DocumentType;
use App\Domain\Guard\DomainGuard;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'number_sequence')]
#[ORM\UniqueConstraint(name: 'UNIQ_NUMBER_SEQUENCE_DOC_YEAR', columns: ['document_type', 'year'])]
class NumberSequence
{
    use UuidTrait;

    public function __construct(
        #[ORM\Column(enumType: DocumentType::class)]
        private(set) readonly DocumentType $documentType,

        #[ORM\Column(type: Types::SMALLINT)]
        private(set) int $year {
            set => DomainGuard::nonNegativeInt($value, 'Year');
        },

        #[ORM\Column(type: Types::INTEGER)]
        private(set) int $nextValue = 1 {
            set => DomainGuard::nonNegativeInt($value, 'Next value');
        },
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function reserveNext(): int
    {
        $current = $this->nextValue;
        $this->nextValue = DomainGuard::nonNegativeInt($this->nextValue + 1, 'Next value');

        return $current;
    }
}
