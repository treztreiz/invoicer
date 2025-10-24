<?php

namespace App\Domain\Entity\Document;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'document_line')]
class DocumentLine
{
    use UuidTrait;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'lines')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private(set) Document $document,

        #[ORM\Column(type: Types::TEXT)]
        private(set) readonly string $description,

        #[ORM\Embedded]
        private(set) readonly Quantity $quantity,

        #[ORM\Embedded]
        private(set) readonly Money $unitPrice,

        #[ORM\Embedded]
        private(set) readonly Money $amountNet,

        #[ORM\Embedded]
        private(set) readonly Money $amountTax,

        #[ORM\Embedded]
        private(set) readonly Money $amountGross,

        #[ORM\Column(type: Types::INTEGER)]
        private(set) int $position,
    ) {
        $this->assertDescription($description);
        $this->assertPosition($position);
    }

    public function reassign(Document $document, int $position): void
    {
        $this->assertPosition($position);

        $this->document = $document;
        $this->position = $position;
    }

    private function assertPosition(int $position): void
    {
        if ($position < 0) {
            throw new InvalidArgumentException('Position must be zero or positive.');
        }
    }

    private function assertDescription(string $description): void
    {
        if (trim($description) === '') {
            throw new InvalidArgumentException('Description cannot be empty.');
        }
    }
}
