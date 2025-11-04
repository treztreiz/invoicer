<?php

declare(strict_types=1);

namespace App\Domain\Entity\Document;

use App\Domain\DTO\DocumentLinePayload;
use App\Domain\Entity\Common\UuidTrait;
use App\Domain\Enum\RateUnit;
use App\Domain\Guard\DomainGuard;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Infrastructure\Doctrine\CheckAware\Attribute\EnumCheck;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'document_line')]
#[EnumCheck(property: 'rateUnit', name: 'CHK_DOCUMENT_LINE_RATE_UNIT')]
class DocumentLine
{
    use UuidTrait;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'lines')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private(set) Document $document,

        #[ORM\Column(type: Types::TEXT)]
        private(set) string $description,

        #[ORM\Embedded]
        private(set) readonly Quantity $quantity,

        #[ORM\Column(enumType: RateUnit::class)]
        private(set) readonly RateUnit $rateUnit,

        #[ORM\Embedded]
        private(set) readonly Money $rate,

        #[ORM\Embedded]
        private(set) readonly AmountBreakdown $amount,

        #[ORM\Column(type: Types::INTEGER)]
        private(set) int $position,
    ) {
        $this->description = DomainGuard::nonEmpty($description, 'Line description');
        $this->position = DomainGuard::nonNegativeInt($position, 'Line position');
    }

    public static function fromPayload(Document $document, DocumentLinePayload $payload): self
    {
        $ctor = new \ReflectionMethod(self::class, '__construct');
        $params = \array_slice($ctor->getParameters(), 1); // skip `$document`
        $arguments = [$document];

        foreach ($params as $parameter) {
            $name = $parameter->getName();

            $arguments[] = property_exists($payload, $name)
                ? $payload->$name
                : throw new \InvalidArgumentException(sprintf('Payload missing `%s`.', $name));
        }

        return new self(...$arguments);
    }

    public function reassign(Document $document, int $position): void
    {
        $this->document = $document;
        $this->position = DomainGuard::nonNegativeInt($position, 'Line position');
    }
}
