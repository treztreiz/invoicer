<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document;

use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Enum\RateUnit;
use App\Domain\Guard\DomainGuard;
use App\Domain\Payload\Document\ComputedLinePayload;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// TODO: ADD UNIQUE POSITION FOR SAME DOCUMENT
#[ORM\Entity]
#[ORM\Table(name: 'document_line')]
#[EnumCheck(property: 'rateUnit', name: 'CHK_DOCUMENT_LINE_RATE_UNIT')]
class DocumentLine
{
    use UuidTrait;

    private function __construct(
        #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'lines')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private(set) readonly Document $document,

        #[ORM\Column(type: Types::TEXT)]
        private(set) string $description {
            set => DomainGuard::nonEmpty($value, 'Line description');
        },

        #[ORM\Embedded]
        private(set) Quantity $quantity,

        #[ORM\Column(enumType: RateUnit::class)]
        private(set) RateUnit $rateUnit,

        #[ORM\Embedded]
        private(set) Money $rate,

        #[ORM\Embedded]
        private(set) AmountBreakdown $amount,

        #[ORM\Column(type: Types::INTEGER)]
        private(set) int $position {
            set => DomainGuard::nonNegativeInt($value, 'Line position');
        },
    ) {
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function fromPayload(Document $document, ComputedLinePayload $payload): self
    {
        return new self(
            document: $document,
            description: $payload->description,
            quantity: $payload->quantity,
            rateUnit: $payload->rateUnit,
            rate: $payload->rate,
            amount: $payload->amount,
            position: $payload->position,
        );
    }

    public function applyPayload(ComputedLinePayload $payload): void
    {
        $this->description = $payload->description;
        $this->quantity = $payload->quantity;
        $this->rateUnit = $payload->rateUnit;
        $this->rate = $payload->rate;
        $this->amount = $payload->amount;
        $this->position = $payload->position;
    }
}
